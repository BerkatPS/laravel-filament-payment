<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Transaction;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $recordTitleAttribute = 'transaction_code';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('transaction_code')
                    ->default(fn () => 'TRX-' . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -4)))
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn ($record) => $record !== null)
                    ->label('Kode Transaksi'),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->default(fn ($livewire) => $livewire->getOwnerRecord()->calculateRemainingAmount())
                    ->label('Jumlah Pembayaran'),
                Forms\Components\Select::make('payment_method')
                    ->options([
                        'cash' => 'Tunai',
                        'bank_transfer' => 'Transfer Bank',
                        'credit_card' => 'Kartu Kredit',
                        'debit_card' => 'Kartu Debit',
                        'ewallet' => 'E-Wallet',
                        'qris' => 'QRIS',
                        'other' => 'Lainnya',
                    ])
                    ->required()
                    ->default('bank_transfer')
                    ->label('Metode Pembayaran'),
                Forms\Components\TextInput::make('reference_number')
                    ->maxLength(255)
                    ->label('Nomor Referensi'),
                Forms\Components\DateTimePicker::make('payment_date')
                    ->required()
                    ->default(now())
                    ->label('Tanggal Pembayaran'),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Tertunda',
                        'completed' => 'Selesai',
                        'failed' => 'Gagal',
                        'refunded' => 'Dikembalikan',
                    ])
                    ->required()
                    ->default('completed')
                    ->label('Status'),
                Forms\Components\Toggle::make('is_manual')
                    ->label('Pembayaran Manual')
                    ->default(true),
                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535)
                    ->label('Catatan'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_code')
                    ->searchable()
                    ->sortable()
                    ->label('Kode Transaksi'),
                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable()
                    ->label('Jumlah'),
                Tables\Columns\TextColumn::make('payment_method')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Tunai',
                        'bank_transfer' => 'Transfer Bank',
                        'credit_card' => 'Kartu Kredit',
                        'debit_card' => 'Kartu Debit',
                        'ewallet' => 'E-Wallet',
                        'qris' => 'QRIS',
                        'other' => 'Lainnya',
                        default => $state,
                    })
                    ->searchable()
                    ->sortable()
                    ->label('Metode Pembayaran'),
                Tables\Columns\TextColumn::make('reference_number')
                    ->searchable()
                    ->sortable()
                    ->label('Nomor Referensi'),
                Tables\Columns\TextColumn::make('payment_date')
                    ->dateTime()
                    ->sortable()
                    ->label('Tanggal Pembayaran'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'gray' => 'refunded',
                    ])
                    ->label('Status'),
                Tables\Columns\IconColumn::make('is_manual')
                    ->boolean()
                    ->label('Manual'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Dibuat'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Tunai',
                        'bank_transfer' => 'Transfer Bank',
                        'credit_card' => 'Kartu Kredit',
                        'debit_card' => 'Kartu Debit',
                        'ewallet' => 'E-Wallet',
                        'qris' => 'QRIS',
                        'other' => 'Lainnya',
                    ])
                    ->label('Metode Pembayaran'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Tertunda',
                        'completed' => 'Selesai',
                        'failed' => 'Gagal',
                        'refunded' => 'Dikembalikan',
                    ])
                    ->label('Status'),
                Tables\Filters\Filter::make('is_manual')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->where('is_manual', true))
                    ->label('Hanya Manual'),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function ($record, $livewire) {
                        // Update invoice status if it's fully paid
                        $invoice = $livewire->getOwnerRecord();
                        if ($record->status === 'completed' && $invoice->calculateRemainingAmount() <= 0) {
                            $invoice->status = 'paid';
                            $invoice->save();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->after(function ($record, $livewire) {
                            // Update invoice status if it's fully paid
                            $invoice = $livewire->getOwnerRecord();
                            if ($record->status === 'completed' && $invoice->calculateRemainingAmount() <= 0) {
                                $invoice->status = 'paid';
                                $invoice->save();
                            }
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->after(function ($record, $livewire) {
                            // Update invoice status if it was previously paid but now has remaining
                            $invoice = $livewire->getOwnerRecord();
                            if ($invoice->status === 'paid' && $invoice->calculateRemainingAmount() > 0) {
                                $invoice->status = 'sent';
                                $invoice->save();
                            }
                        }),
                    Tables\Actions\Action::make('refund')
                        ->label('Refund')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($record, $livewire) {
                            // Create refund transaction
                            Transaction::create([
                                'transaction_code' => 'REF-' . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -4)),
                                'customer_id' => $record->customer_id,
                                'invoice_id' => $record->invoice_id,
                                'amount' => -abs($record->amount),
                                'payment_method' => $record->payment_method,
                                'reference_number' => 'Refund for ' . $record->transaction_code,
                                'payment_date' => now(),
                                'status' => 'refunded',
                                'is_manual' => true,
                                'notes' => 'Refund for transaction ' . $record->transaction_code,
                                'refunded_transaction_id' => $record->id,
                            ]);
                            
                            // Update original transaction status
                            $record->status = 'refunded';
                            $record->save();
                            
                            // Update invoice status
                            $invoice = $livewire->getOwnerRecord();
                            if ($invoice->status === 'paid' && $invoice->calculateRemainingAmount() > 0) {
                                $invoice->status = 'sent';
                                $invoice->save();
                            }
                        })
                        ->visible(fn ($record) => $record->status === 'completed'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

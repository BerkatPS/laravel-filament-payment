<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Invoice;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $recordTitleAttribute = 'transaction_number';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('transaction_number')
                    ->default(fn () => 'TRX-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4)))
                    ->label('Nomor Transaksi')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn ($record) => $record !== null),
                Forms\Components\Select::make('invoice_id')
                    ->label('Invoice')
                    ->options(function ($livewire) {
                        $customerId = $livewire->getOwnerRecord()->id;
                        return Invoice::where('customer_id', $customerId)
                            ->whereIn('status', ['sent', 'overdue'])
                            ->get()
                            ->pluck('invoice_number', 'id');
                    })
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        if ($state) {
                            $invoice = Invoice::find($state);
                            if ($invoice) {
                                $set('amount', $invoice->calculateRemainingAmount());
                            }
                        }
                    }),
                Forms\Components\TextInput::make('amount')
                    ->label('Jumlah')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
                Forms\Components\Select::make('payment_method')
                    ->options([
                        'credit_card' => 'Kartu Kredit',
                        'debit_card' => 'Kartu Debit',
                        'bank_transfer' => 'Transfer Bank',
                        'e_wallet' => 'E-Wallet',
                        'qris' => 'QRIS',
                        'cash' => 'Tunai',
                        'paypal' => 'PayPal',
                    ])
                    ->required()
                    ->label('Metode Pembayaran'),
                Forms\Components\TextInput::make('payment_reference')
                    ->label('Referensi Pembayaran')
                    ->maxLength(255),
                Forms\Components\Textarea::make('payment_details')
                    ->label('Detail Pembayaran')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('payment_date')
                    ->label('Tanggal Pembayaran')
                    ->default(now())
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Menunggu',
                        'completed' => 'Selesai',
                        'failed' => 'Gagal',
                        'refunded' => 'Dikembalikan',
                    ])
                    ->default('completed')
                    ->required()
                    ->label('Status'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_number')
                    ->searchable()
                    ->sortable()
                    ->label('Nomor Transaksi'),
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->searchable()
                    ->sortable()
                    ->label('Nomor Invoice'),
                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable()
                    ->label('Jumlah'),
                Tables\Columns\TextColumn::make('payment_method')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'credit_card' => 'Kartu Kredit',
                        'debit_card' => 'Kartu Debit',
                        'bank_transfer' => 'Transfer Bank',
                        'e_wallet' => 'E-Wallet',
                        'qris' => 'QRIS',
                        'cash' => 'Tunai',
                        'paypal' => 'PayPal',
                        default => $state,
                    })
                    ->label('Metode Pembayaran'),
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
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Dibuat'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu',
                        'completed' => 'Selesai',
                        'failed' => 'Gagal',
                        'refunded' => 'Dikembalikan',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'credit_card' => 'Kartu Kredit',
                        'debit_card' => 'Kartu Debit',
                        'bank_transfer' => 'Transfer Bank',
                        'e_wallet' => 'E-Wallet',
                        'qris' => 'QRIS',
                        'cash' => 'Tunai',
                        'paypal' => 'PayPal',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->using(function (array $data, string $model) {
                        $transaction = $model::create($data);
                        
                        // Update invoice status if necessary
                        if (isset($data['invoice_id']) && $data['invoice_id']) {
                            $invoice = Invoice::find($data['invoice_id']);
                            if ($invoice && $data['status'] === 'completed') {
                                $invoice->updatePaymentStatus();
                            }
                        }
                        
                        return $transaction;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('refund')
                    ->label('Kembalikan Dana')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->action(function ($record) {
                        $record->refund();
                    })
                    ->visible(fn ($record) => $record->canBeRefunded()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

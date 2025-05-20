<?php

namespace App\Filament\FinanceResources;

use App\Filament\FinanceResources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Colors\Color;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationGroup = 'Keuangan';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $recordTitleAttribute = 'transaction_number';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi Transaksi')
                            ->schema([
                                Forms\Components\TextInput::make('transaction_code')
                                    ->label('Kode Transaksi')
                                    ->disabled(),
                                Forms\Components\TextInput::make('transaction_number')
                                    ->label('Nomor Transaksi')
                                    ->disabled(),
                                Forms\Components\Select::make('invoice_id')
                                    ->label('Invoice')
                                    ->relationship('invoice', 'invoice_number')
                                    ->disabled(),
                                Forms\Components\Select::make('customer_id')
                                    ->label('Pelanggan')
                                    ->relationship('customer', 'name')
                                    ->disabled(),
                                Forms\Components\TextInput::make('amount')
                                    ->label('Jumlah')
                                    ->prefix('Rp')
                                    ->numeric()
                                    ->disabled(),
                                Forms\Components\DateTimePicker::make('payment_date')
                                    ->label('Tanggal Pembayaran')
                                    ->disabled(),
                            ])->columns(2),
                        Forms\Components\Section::make('Metode Pembayaran')
                            ->schema([
                                Forms\Components\Select::make('payment_method')
                                    ->label('Metode Pembayaran')
                                    ->options([
                                        'credit_card' => 'Kartu Kredit',
                                        'debit_card' => 'Kartu Debit',
                                        'bank_transfer' => 'Transfer Bank',
                                        'e_wallet' => 'E-Wallet',
                                        'qris' => 'QRIS',
                                        'cash' => 'Tunai',
                                        'paypal' => 'PayPal',
                                    ])
                                    ->disabled(),
                                Forms\Components\TextInput::make('reference_number')
                                    ->label('Nomor Referensi')
                                    ->disabled(),
                                Forms\Components\FileUpload::make('payment_proof')
                                    ->label('Bukti Pembayaran')
                                    ->disk('public')
                                    ->directory('payment-proofs')
                                    ->visibility('public')
                                    ->downloadable()
                                    ->openable()
                                    ->disabled(),
                            ])->columns(2),
                    ])->columnSpan(2),
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Status')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Status Transaksi')
                                    ->options([
                                        'pending' => 'Menunggu',
                                        'completed' => 'Selesai',
                                        'failed' => 'Gagal',
                                        'refunded' => 'Refund',
                                        'cancelled' => 'Dibatalkan',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        // Jika status berubah menjadi completed, set notes
                                        if ($state === 'completed') {
                                            $set('notes', 'Pembayaran telah diverifikasi oleh ' . auth()->user()->name);
                                        } elseif ($state === 'failed') {
                                            $set('notes', 'Pembayaran ditolak oleh ' . auth()->user()->name);
                                        }
                                    }),
                                Forms\Components\Textarea::make('notes')
                                    ->label('Catatan')
                                    ->placeholder('Tambahkan catatan untuk transaksi ini...')
                                    ->rows(5),
                            ]),
                    ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_number')
                    ->label('Nomor Transaksi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_code')
                    ->label('Kode Transaksi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Nomor Invoice')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'credit_card' => 'Kartu Kredit',
                        'debit_card' => 'Kartu Debit',
                        'bank_transfer' => 'Transfer Bank',
                        'e_wallet' => 'E-Wallet',
                        'qris' => 'QRIS',
                        'cash' => 'Tunai',
                        'paypal' => 'PayPal',
                        default => $state,
                    })
                    ->colors([
                        'warning' => fn ($state) => in_array($state, ['cash']),
                        'success' => fn ($state) => in_array($state, ['bank_transfer', 'qris']),
                        'primary' => fn ($state) => in_array($state, ['credit_card', 'debit_card', 'e_wallet', 'paypal']),
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'completed' => 'Selesai',
                        'failed' => 'Gagal',
                        'refunded' => 'Refund',
                        'cancelled' => 'Dibatalkan',
                        default => $state,
                    })
                    ->colors([
                        'warning' => fn ($state) => $state === 'pending',
                        'success' => fn ($state) => $state === 'completed',
                        'danger' => fn ($state) => in_array($state, ['failed', 'cancelled']),
                        'info' => fn ($state) => $state === 'refunded',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Tanggal Pembayaran')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu',
                        'completed' => 'Selesai',
                        'failed' => 'Gagal',
                        'refunded' => 'Refund',
                        'cancelled' => 'Dibatalkan',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'credit_card' => 'Kartu Kredit',
                        'debit_card' => 'Kartu Debit',
                        'bank_transfer' => 'Transfer Bank',
                        'e_wallet' => 'E-Wallet',
                        'qris' => 'QRIS',
                        'cash' => 'Tunai',
                        'paypal' => 'PayPal',
                    ]),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dari'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('approve')
                        ->label('Setujui Pembayaran')
                        ->icon('heroicon-o-check-circle')
                        ->color(Color::Green)
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            // Update transaction status
                            $record->status = 'completed';
                            $record->notes = 'Pembayaran telah diverifikasi oleh ' . auth()->user()->name;
                            $record->save();
                            
                            // Update invoice status if needed
                            if ($record->invoice) {
                                $invoice = $record->invoice;
                                $remainingAmount = $invoice->calculateRemainingAmount();
                                
                                if ($remainingAmount <= 0) {
                                    $invoice->status = 'paid';
                                    $invoice->save();
                                }
                            }
                            
                            // Send notification to customer
                            if ($record->customer && $record->customer->user) {
                                $record->customer->user->notify(new \App\Notifications\PaymentApproved($record));
                            }
                        })
                        ->visible(fn ($record) => $record->status === 'pending'),
                    Tables\Actions\Action::make('reject')
                        ->label('Tolak Pembayaran')
                        ->icon('heroicon-o-x-circle')
                        ->color(Color::Red)
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            // Update transaction status
                            $record->status = 'failed';
                            $record->notes = 'Pembayaran ditolak oleh ' . auth()->user()->name;
                            $record->save();
                            
                            // Send notification to customer
                            if ($record->customer && $record->customer->user) {
                                $record->customer->user->notify(new \App\Notifications\PaymentRejected($record));
                            }
                        })
                        ->visible(fn ($record) => $record->status === 'pending'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('payment_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}

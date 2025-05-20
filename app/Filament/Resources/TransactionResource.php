<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Transaction;
use App\Models\Customer;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationGroup = 'Keuangan';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $recordTitleAttribute = 'transaction_code';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi Transaksi')
                            ->schema([
                                Forms\Components\TextInput::make('transaction_code')
                                    ->default(fn () => 'TRX-' . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -4)))
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->disabled(fn ($record) => $record !== null)
                                    ->label('Kode Transaksi'),
                                Forms\Components\Select::make('customer_id')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn (callable $set) => $set('invoice_id', null))
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255)
                                            ->label('Nama Pelanggan'),
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(),
                                        Forms\Components\TextInput::make('phone')
                                            ->tel()
                                            ->maxLength(20),
                                    ])
                                    ->label('Pelanggan'),
                                Forms\Components\Select::make('invoice_id')
                                    ->label('Invoice')
                                    ->options(function (callable $get) {
                                        $customerId = $get('customer_id');
                                        if (!$customerId) {
                                            return [];
                                        }
                                        return Invoice::where('customer_id', $customerId)
                                            ->where(function ($query) {
                                                $query->where('status', '!=', 'paid')
                                                    ->where('status', '!=', 'cancelled');
                                            })
                                            ->pluck('invoice_number', 'id')
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                        if ($state) {
                                            $invoice = Invoice::find($state);
                                            if ($invoice) {
                                                $remainingAmount = $invoice->calculateRemainingAmount();
                                                $set('amount', $remainingAmount);
                                            }
                                        }
                                    })
                                    ->helperText('Hanya invoice yang belum lunas atau dibatalkan'),
                            ])->columns(2),
                        Forms\Components\Section::make('Detail Pembayaran')
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
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
                            ])->columns(2),
                        Forms\Components\Section::make('Catatan')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->maxLength(65535)
                                    ->label('Catatan')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Ringkasan')
                            ->schema([
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Dibuat Pada')
                                    ->content(fn (?Transaction $record): string => $record?->created_at?->diffForHumans() ?? '-'),
                                Forms\Components\Placeholder::make('updated_at')
                                    ->label('Terakhir Diubah')
                                    ->content(fn (?Transaction $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
                            ]),
                        Forms\Components\Section::make('Update Status Invoice')
                            ->schema([
                                Forms\Components\Checkbox::make('update_invoice_status')
                                    ->label('Update Status Invoice')
                                    ->default(true)
                                    ->visible(fn ($record) => $record === null)
                                    ->helperText('Jika dicentang, status invoice akan diperbarui secara otomatis menjadi Dibayar jika invoice telah lunas'),
                            ])
                            ->visible(fn (callable $get) => (bool) $get('invoice_id')),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_code')
                    ->searchable()
                    ->sortable()
                    ->label('Kode Transaksi'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable()
                    ->label('Pelanggan'),
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->searchable()
                    ->sortable()
                    ->label('Invoice'),
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
                Tables\Filters\SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Pelanggan'),
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
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\Action::make('approve')
                        ->label('Setujui Pembayaran')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            // Update transaction status
                            $record->status = 'completed';
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
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            // Update transaction status
                            $record->status = 'failed';
                            $record->save();
                            
                            // Send notification to customer
                            if ($record->customer && $record->customer->user) {
                                $record->customer->user->notify(new \App\Notifications\PaymentRejected($record));
                            }
                        })
                        ->visible(fn ($record) => $record->status === 'pending'),
                    Tables\Actions\Action::make('refund')
                        ->label('Refund')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($record) {
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
                            
                            // Update invoice status if needed
                            if ($record->invoice) {
                                $invoice = $record->invoice;
                                if ($invoice->status === 'paid' && $invoice->calculateRemainingAmount() > 0) {
                                    $invoice->status = 'sent';
                                    $invoice->save();
                                }
                            }
                        })
                        ->visible(fn ($record) => $record->status === 'completed'),
                    Tables\Actions\Action::make('receipt')
                        ->label('Cetak Kuitansi')
                        ->icon('heroicon-o-document-text')
                        ->url(fn ($record) => route('transactions.receipt', $record))
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => $record->status === 'completed'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

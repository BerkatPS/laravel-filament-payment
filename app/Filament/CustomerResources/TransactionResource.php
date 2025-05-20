<?php

namespace App\Filament\CustomerResources;

use App\Filament\CustomerResources\TransactionResource\Pages;
use App\Models\Transaction;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationLabel = 'Pembayaran';
    
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
                                    ->disabled()
                                    ->label('Kode Transaksi'),
                                Forms\Components\Select::make('invoice_id')
                                    ->label('Invoice')
                                    ->options(function () {
                                        $user = Auth::user();
                                        $customer = $user->customer;
                                        
                                        if (!$customer) {
                                            return [];
                                        }
                                        
                                        return Invoice::where('customer_id', $customer->id)
                                            ->whereIn('status', ['draft', 'sent', 'overdue'])
                                            ->pluck('invoice_number', 'id')
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if ($state) {
                                            $invoice = Invoice::find($state);
                                            if ($invoice) {
                                                $remainingAmount = $invoice->calculateRemainingAmount();
                                                $set('amount', $remainingAmount);
                                            }
                                        }
                                    })
                                    ->helperText('Pilih invoice yang ingin dibayar'),
                            ])->columns(1),
                        Forms\Components\Section::make('Detail Pembayaran')
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->label('Jumlah Pembayaran'),
                                Forms\Components\Select::make('payment_method')
                                    ->options([
                                        'bank_transfer' => 'Transfer Bank',
                                        'credit_card' => 'Kartu Kredit',
                                        'debit_card' => 'Kartu Debit',
                                        'ewallet' => 'E-Wallet',
                                        'qris' => 'QRIS',
                                    ])
                                    ->required()
                                    ->default('bank_transfer')
                                    ->label('Metode Pembayaran'),
                                Forms\Components\TextInput::make('reference_number')
                                    ->maxLength(255)
                                    ->required()
                                    ->label('Nomor Referensi/Bukti Transfer'),
                                Forms\Components\FileUpload::make('payment_proof')
                                    ->label('Bukti Pembayaran')
                                    ->required(fn (string $context): bool => $context === 'create')
                                    ->disk('public')
                                    ->directory('payment-proofs')
                                    ->visibility('public')
                                    ->downloadable()
                                    ->openable()
                                    ->imagePreviewHeight('250')
                                    ->acceptable(['image/jpeg', 'image/png', 'image/webp', 'application/pdf']),
                                Forms\Components\DateTimePicker::make('payment_date')
                                    ->required()
                                    ->default(now())
                                    ->label('Tanggal Pembayaran'),
                                Forms\Components\Hidden::make('status')
                                    ->default('pending'),
                                Forms\Components\Hidden::make('is_manual')
                                    ->default(true),
                            ])->columns(1),
                        Forms\Components\Section::make('Catatan')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->maxLength(65535)
                                    ->placeholder('Tambahkan catatan jika diperlukan')
                                    ->label('Catatan')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi')
                            ->schema([
                                Forms\Components\Placeholder::make('payment_info')
                                    ->content('Setelah melakukan pembayaran, mohon upload bukti pembayaran. Admin akan memverifikasi pembayaran Anda dalam 1x24 jam.'),
                            ]),
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
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Tertunda',
                        'completed' => 'Selesai',
                        'failed' => 'Gagal',
                        'refunded' => 'Dikembalikan',
                        default => $state,
                    })
                    ->label('Status'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Dibuat'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'bank_transfer' => 'Transfer Bank',
                        'credit_card' => 'Kartu Kredit',
                        'debit_card' => 'Kartu Debit',
                        'ewallet' => 'E-Wallet',
                        'qris' => 'QRIS',
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
            ->actions([
                Tables\Actions\Action::make('receipt')
                    ->label('Cetak Kuitansi')
                    ->icon('heroicon-o-document-text')
                    ->url(fn ($record) => route('transactions.receipt', $record))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->status === 'completed'),
                Tables\Actions\ViewAction::make(),
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
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $customer = $user->customer;
        
        if (!$customer) {
            return parent::getEloquentQuery()->where('id', null); // No transactions if no customer
        }
        
        return parent::getEloquentQuery()
            ->where('customer_id', $customer->id);
    }
}

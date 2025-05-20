<?php

namespace App\Filament\FinanceResources;

use App\Filament\FinanceResources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Discount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = 'Keuangan';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $recordTitleAttribute = 'invoice_number';
    
    protected static ?string $slug = 'finance/invoices';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi Invoice')
                            ->schema([
                                Forms\Components\TextInput::make('invoice_number')
                                    ->default(fn () => 'INV-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4)))
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->disabled(fn ($record) => $record !== null)
                                    ->label('Nomor Invoice'),
                                Forms\Components\Select::make('customer_id')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
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
                                Forms\Components\DatePicker::make('issue_date')
                                    ->label('Tanggal Diterbitkan')
                                    ->default(now())
                                    ->required(),
                                Forms\Components\DatePicker::make('due_date')
                                    ->label('Tanggal Jatuh Tempo')
                                    ->default(fn () => now()->addDays(7))
                                    ->required(),
                            ])->columns(2),
                        Forms\Components\Section::make('Detail Tagihan')
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn (callable $set, callable $get) => 
                                        self::calculateFinalAmount($set, $get)
                                    ),
                                Forms\Components\TextInput::make('tax_amount')
                                    ->label('Jumlah Pajak')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->reactive()
                                    ->afterStateUpdated(fn (callable $set, callable $get) => 
                                        self::calculateFinalAmount($set, $get)
                                    ),
                                Forms\Components\Select::make('discount_id')
                                    ->relationship('discount', 'name')
                                    ->label('Diskon')
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(fn (callable $set, callable $get) => 
                                        self::calculateFinalAmount($set, $get)
                                    ),
                                Forms\Components\TextInput::make('discount_amount')
                                    ->label('Jumlah Diskon')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(),
                                Forms\Components\TextInput::make('final_amount')
                                    ->label('Jumlah Akhir')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'sent' => 'Terkirim',
                                        'paid' => 'Dibayar',
                                        'overdue' => 'Terlambat',
                                        'cancelled' => 'Dibatalkan',
                                    ])
                                    ->default('draft')
                                    ->required()
                                    ->label('Status'),
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
                                    ->content(fn (?Invoice $record): string => $record?->created_at?->diffForHumans() ?? '-'),
                                Forms\Components\Placeholder::make('updated_at')
                                    ->label('Terakhir Diubah')
                                    ->content(fn (?Invoice $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
                            ]),
                        Forms\Components\Section::make('Status Pembayaran')
                            ->schema([
                                Forms\Components\Placeholder::make('status_badge')
                                    ->label('Status')
                                    ->content(fn (?Invoice $record): string => $record ? ucfirst($record->status) : 'Draft'),
                                Forms\Components\Placeholder::make('paid_amount')
                                    ->label('Jumlah Dibayar')
                                    ->content(fn (?Invoice $record): string => $record ? 'Rp ' . number_format($record->calculatePaidAmount(), 2, ',', '.') : '-'),
                                Forms\Components\Placeholder::make('remaining_amount')
                                    ->label('Sisa Pembayaran')
                                    ->content(fn (?Invoice $record): string => $record ? 'Rp ' . number_format($record->calculateRemainingAmount(), 2, ',', '.') : '-'),
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
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable()
                    ->label('Nomor Invoice'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable()
                    ->label('Pelanggan'),
                Tables\Columns\TextColumn::make('issue_date')
                    ->date()
                    ->sortable()
                    ->label('Tanggal Diterbitkan'),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->label('Tanggal Jatuh Tempo'),
                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable()
                    ->label('Jumlah'),
                Tables\Columns\TextColumn::make('final_amount')
                    ->money('IDR')
                    ->sortable()
                    ->label('Jumlah Akhir'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'overdue',
                        'warning' => 'draft',
                        'success' => 'paid',
                        'primary' => 'sent',
                        'gray' => 'cancelled',
                    ])
                    ->label('Status'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Pelanggan'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Terkirim',
                        'paid' => 'Dibayar',
                        'overdue' => 'Terlambat',
                        'cancelled' => 'Dibatalkan',
                    ]),
                Tables\Filters\Filter::make('unpaid')
                    ->query(fn (Builder $query) => $query->whereIn('status', ['draft', 'sent', 'overdue']))
                    ->label('Belum Dibayar'),
                Tables\Filters\Filter::make('due_today')
                    ->query(fn (Builder $query) => $query->where('due_date', now()->format('Y-m-d')))
                    ->label('Jatuh Tempo Hari Ini'),
                Tables\Filters\Filter::make('overdue')
                    ->query(fn (Builder $query) => $query->where('due_date', '<', now()->format('Y-m-d'))->whereNotIn('status', ['paid', 'cancelled']))
                    ->label('Terlambat'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('markAsPaid')
                        ->label('Tandai Dibayar')
                        ->icon('heroicon-o-currency-dollar')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->status = 'paid';
                            $record->save();
                        })
                        ->visible(fn ($record) => in_array($record->status, ['draft', 'sent', 'overdue'])),
                    Tables\Actions\Action::make('print')
                        ->label('Cetak')
                        ->icon('heroicon-o-printer')
                        ->url(fn ($record) => route('invoices.print', $record))
                        ->openUrlInNewTab(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('markAsPaid')
                        ->label('Tandai Dibayar')
                        ->icon('heroicon-o-currency-dollar')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each(function ($record) {
                            $record->status = 'paid';
                            $record->save();
                        })),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            // Relasi Manager akan didefinisikan di folder Pages
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
    
    private static function calculateFinalAmount($set, $get)
    {
        $amount = (float)$get('amount') ?: 0;
        $taxAmount = (float)$get('tax_amount') ?: 0;
        $discountAmount = 0;
        
        if ($get('discount_id')) {
            $discount = Discount::find($get('discount_id'));
            if ($discount) {
                $discountAmount = $discount->calculateDiscount($amount);
            }
        }
        
        $finalAmount = $amount + $taxAmount - $discountAmount;
        
        $set('discount_amount', $discountAmount);
        $set('final_amount', max(0, $finalAmount));
    }
}

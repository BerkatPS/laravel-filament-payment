<?php

namespace App\Filament\CustomerResources;

use App\Filament\CustomerResources\InvoiceResource\Pages;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = 'Tagihan Saya';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $recordTitleAttribute = 'invoice_number';
    
    protected static ?string $slug = 'my-invoices';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi Invoice')
                            ->schema([
                                Forms\Components\TextInput::make('invoice_number')
                                    ->label('Nomor Invoice')
                                    ->disabled(),
                                Forms\Components\TextInput::make('customer.name')
                                    ->label('Pelanggan')
                                    ->disabled(),
                                Forms\Components\DatePicker::make('issue_date')
                                    ->label('Tanggal Diterbitkan')
                                    ->disabled(),
                                Forms\Components\DatePicker::make('due_date')
                                    ->label('Tanggal Jatuh Tempo')
                                    ->disabled(),
                            ])->columns(2),
                        Forms\Components\Section::make('Detail Tagihan')
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('Jumlah')
                                    ->prefix('Rp')
                                    ->disabled(),
                                Forms\Components\TextInput::make('tax_amount')
                                    ->label('Jumlah Pajak')
                                    ->prefix('Rp')
                                    ->disabled(),
                                Forms\Components\TextInput::make('discount_amount')
                                    ->label('Jumlah Diskon')
                                    ->prefix('Rp')
                                    ->disabled(),
                                Forms\Components\TextInput::make('final_amount')
                                    ->label('Jumlah Akhir')
                                    ->prefix('Rp')
                                    ->disabled(),
                                Forms\Components\TextInput::make('status')
                                    ->label('Status')
                                    ->disabled(),
                            ])->columns(2),
                        Forms\Components\Section::make('Catatan')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('Catatan')
                                    ->disabled()
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Status Pembayaran')
                            ->schema([
                                Forms\Components\Placeholder::make('status_badge')
                                    ->label('Status')
                                    ->content(fn (?Invoice $record): string => $record ? ucfirst($record->status) : '-'),
                                Forms\Components\Placeholder::make('paid_amount')
                                    ->label('Jumlah Dibayar')
                                    ->content(fn (?Invoice $record): string => $record ? 'Rp ' . number_format($record->calculatePaidAmount(), 2, ',', '.') : '-'),
                                Forms\Components\Placeholder::make('remaining_amount')
                                    ->label('Sisa Pembayaran')
                                    ->content(fn (?Invoice $record): string => $record ? 'Rp ' . number_format($record->calculateRemainingAmount(), 2, ',', '.') : '-'),
                                Forms\Components\Placeholder::make('due_date_info')
                                    ->label('Info Jatuh Tempo')
                                    ->content(function (?Invoice $record) {
                                        if (!$record) return '-';
                                        
                                        if ($record->status === 'paid') {
                                            return 'Lunas';
                                        }
                                        
                                        if ($record->due_date < now()) {
                                            return 'Telah jatuh tempo ' . $record->due_date->diffForHumans();
                                        }
                                        
                                        return 'Jatuh tempo ' . $record->due_date->diffForHumans();
                                    }),
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
                Tables\Columns\TextColumn::make('issue_date')
                    ->date()
                    ->sortable()
                    ->label('Tanggal Diterbitkan'),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->label('Tanggal Jatuh Tempo'),
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('bayar')
                    ->label('Bayar Sekarang')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->url(fn (Invoice $record) => route('customer.payment.create', ['invoice' => $record->id]))
                    ->visible(fn (Invoice $record) => in_array($record->status, ['draft', 'sent', 'overdue'])),
                Tables\Actions\Action::make('print')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->url(fn ($record) => route('invoices.print', $record))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            // Relasi dengan transaksi akan didefinisikan di halaman detail
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        // Filter invoice untuk hanya menampilkan milik pengguna saat ini
        return parent::getEloquentQuery()
            ->whereHas('customer', function (Builder $query) {
                $query->where('email', Auth::user()->email);
            });
    }
}

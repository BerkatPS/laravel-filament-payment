<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\Support\Htmlable;

class LatestTransactions extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function getTableHeading(): Htmlable|string|null
    {
        return new HtmlString('<span class="text-lg font-bold">Transaksi Terbaru</span>');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->with(['customer', 'invoice'])
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('transaction_code')
                    ->label('Kode Transaksi')
                    ->searchable(),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),

                TextColumn::make('invoice.invoice_number')
                    ->label('Nomor Invoice')
                    ->searchable(),

                TextColumn::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'credit_card' => 'Kartu Kredit',
                        'debit_card' => 'Kartu Debit',
                        'bank_transfer' => 'Transfer Bank',
                        'e_wallet' => 'E-Wallet',
                        'qris' => 'QRIS',
                        'cash' => 'Tunai',
                        'paypal' => 'PayPal',
                        default => $state,
                    }),

                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i'),

                ImageColumn::make('payment_proof')
                    ->label('Bukti')
                    ->circular()
                    ->visibility('visible')
            ])
            ->paginated(false);
    }
}

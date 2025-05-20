<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    protected function getStats(): array
    {
        // Hitung total pendapatan dari invoice yang dibayar
        $paidInvoices = Invoice::where('status', 'paid')->get();
        $totalRevenue = 0;
        foreach ($paidInvoices as $invoice) {
            $totalRevenue += $invoice->final_amount;
        }
        
        return [
            Stat::make('Total Pelanggan', Customer::count())
                ->description('Jumlah total pelanggan')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Total Invoice', Invoice::count())
                ->description('Jumlah total invoice')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Total Pengguna', User::count())
                ->description('Jumlah total pengguna sistem')
                ->descriptionIcon('heroicon-m-user')
                ->color('warning'),

            Stat::make('Total Transaksi', Transaction::count())
                ->description('Jumlah total transaksi')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Invoice Belum Dibayar', Invoice::whereIn('status', ['draft', 'sent', 'overdue'])->count())
                ->description('Invoice yang belum dibayar')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),

            Stat::make('Total Pendapatan', 'Rp ' . number_format($totalRevenue, 0, ',', '.'))
                ->description('Total pendapatan dari invoice yang dibayar')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}

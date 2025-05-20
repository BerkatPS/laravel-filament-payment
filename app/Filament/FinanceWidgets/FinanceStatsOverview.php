<?php

namespace App\Filament\FinanceWidgets;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class FinanceStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Pelanggan', Customer::count())
                ->description('Jumlah total pelanggan')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
                
            Stat::make('Total Invoice', Invoice::count())
                ->description('Jumlah total invoice')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
                
            Stat::make('Invoice Belum Dibayar', Invoice::whereIn('status', ['draft', 'sent', 'overdue'])->count())
                ->description('Invoice yang belum dibayar')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),
                
            Stat::make('Invoice Jatuh Tempo Hari Ini', Invoice::where('due_date', Carbon::today()->format('Y-m-d'))->whereIn('status', ['draft', 'sent'])->count())
                ->description('Invoice yang jatuh tempo hari ini')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('warning'),
                
            Stat::make('Total Pendapatan', function () {
                $paidInvoices = Invoice::where('status', 'paid')->get();
                $total = 0;
                
                foreach ($paidInvoices as $invoice) {
                    $total += $invoice->final_amount;
                }
                
                return 'Rp ' . number_format($total, 0, ',', '.');
            })
                ->description('Total pendapatan dari invoice yang dibayar')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
                
            Stat::make('Pendapatan Bulan Ini', function () {
                $paidInvoices = Invoice::where('status', 'paid')
                    ->whereMonth('updated_at', Carbon::now()->month)
                    ->whereYear('updated_at', Carbon::now()->year)
                    ->get();
                    
                $total = 0;
                
                foreach ($paidInvoices as $invoice) {
                    $total += $invoice->final_amount;
                }
                
                return 'Rp ' . number_format($total, 0, ',', '.');
            })
                ->description('Pendapatan dari invoice yang dibayar bulan ini')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),
        ];
    }
}

<?php

namespace App\Filament\FinanceWidgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class TransactionStats extends BaseWidget
{
    protected function getStats(): array
    {
        // Total Transaksi Pending
        $pendingTransactionsCount = Transaction::where('status', 'pending')->count();
        $pendingTransactionsAmount = Transaction::where('status', 'pending')
            ->sum('amount');
            
        // Total Transaksi Selesai Bulan Ini
        $currentMonthCompletedCount = Transaction::where('status', 'completed')
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->count();
        $currentMonthCompletedAmount = Transaction::where('status', 'completed')
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');
            
        // Total Transaksi per Metode Pembayaran
        $paymentMethodStats = Transaction::where('status', 'completed')
            ->select('payment_method', DB::raw('count(*) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->toArray();
            
        // Format data for display
        $paymentMethodsFormatted = [];
        $paymentMethods = [
            'credit_card' => 'Kartu Kredit',
            'debit_card' => 'Kartu Debit',
            'bank_transfer' => 'Transfer Bank',
            'e_wallet' => 'E-Wallet',
            'qris' => 'QRIS',
            'cash' => 'Tunai',
            'paypal' => 'PayPal',
        ];
        
        foreach ($paymentMethods as $key => $label) {
            $paymentMethodsFormatted[] = $label . ': ' . ($paymentMethodStats[$key] ?? 0);
        }
        
        return [
            Stat::make('Transaksi Menunggu Persetujuan', $pendingTransactionsCount)
                ->description('Total Rp ' . number_format($pendingTransactionsAmount, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
                
            Stat::make('Transaksi Bulan Ini', $currentMonthCompletedCount)
                ->description('Total Rp ' . number_format($currentMonthCompletedAmount, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
                
            Stat::make('Metode Pembayaran', implode('\n', array_slice($paymentMethodsFormatted, 0, 3)))
                ->description(implode('\n', array_slice($paymentMethodsFormatted, 3)))
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('primary'),
        ];
    }
}

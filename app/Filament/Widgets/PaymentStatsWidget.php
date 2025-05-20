<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class PaymentStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalAmount = Payment::where('status', 'completed')
            ->sum('amount');

        $pendingAmount = Payment::where('status', 'pending')
            ->sum('amount');

        $todayCount = Payment::whereDate('payment_date', today())
            ->count();

        return [
            Stat::make('Total Pendapatan', 'Rp ' . number_format($totalAmount, 0, ',', '.'))
                ->description('Total pembayaran yang telah selesai')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Pembayaran Tertunda', 'Rp ' . number_format($pendingAmount, 0, ',', '.'))
                ->description('Total pembayaran yang masih pending')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('Transaksi Hari Ini', $todayCount)
                ->description('Jumlah transaksi hari ini')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),
        ];
    }
}

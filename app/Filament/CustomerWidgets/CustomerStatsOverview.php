<?php

namespace App\Filament\CustomerWidgets;

use App\Models\Invoice;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CustomerStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $customer = $user->customer;
        
        if (!$customer) {
            return [
                Stat::make('Informasi', 'Akun Anda belum terkait dengan data pelanggan')
                    ->description('Hubungi admin untuk bantuan')
                    ->descriptionIcon('heroicon-m-information-circle')
                    ->color('danger'),
            ];
        }
        
        $totalInvoices = Invoice::whereHas('customer', function ($query) use ($user) {
            $query->where('email', $user->email);
        })->count();
        
        $unpaidInvoices = Invoice::whereHas('customer', function ($query) use ($user) {
            $query->where('email', $user->email);
        })->whereIn('status', ['draft', 'sent', 'overdue'])->count();
        
        $overdueInvoices = Invoice::whereHas('customer', function ($query) use ($user) {
            $query->where('email', $user->email);
        })->where('status', 'overdue')->count();
        
        $totalPaid = Invoice::whereHas('customer', function ($query) use ($user) {
            $query->where('email', $user->email);
        })->where('status', 'paid')->sum('final_amount');
        
        $upcomingInvoice = Invoice::whereHas('customer', function ($query) use ($user) {
            $query->where('email', $user->email);
        })->whereIn('status', ['draft', 'sent'])
          ->where('due_date', '>=', Carbon::today())
          ->orderBy('due_date')
          ->first();
          
        // Evaluasi nilai description sebelumnya daripada menggunakan closure
        $upcomingInvoiceDescription = 'Anda tidak memiliki tagihan mendatang';
        if ($upcomingInvoice) {
            $upcomingInvoiceDescription = 'Jatuh tempo ' . $upcomingInvoice->due_date->diffForHumans();
        }
        
        // Evaluasi nilai state sebelumnya daripada menggunakan closure
        $upcomingInvoiceValue = 'Tidak ada';
        if ($upcomingInvoice) {
            $upcomingInvoiceValue = 'Rp ' . number_format($upcomingInvoice->final_amount, 0, ',', '.');
        }
        
        return [
            Stat::make('Total Tagihan', $totalInvoices)
                ->description('Jumlah tagihan Anda')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
                
            Stat::make('Tagihan Belum Dibayar', $unpaidInvoices)
                ->description($unpaidInvoices > 0 ? 'Anda memiliki tagihan yang belum dibayar' : 'Semua tagihan telah dibayar')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color($unpaidInvoices > 0 ? 'danger' : 'success'),
                
            Stat::make('Tagihan Terlambat', $overdueInvoices)
                ->description($overdueInvoices > 0 ? 'Anda memiliki tagihan yang terlambat' : 'Tidak ada tagihan terlambat')
                ->descriptionIcon('heroicon-m-clock')
                ->color($overdueInvoices > 0 ? 'danger' : 'success'),
                
            Stat::make('Total Pembayaran', 'Rp ' . number_format($totalPaid, 0, ',', '.'))
                ->description('Total pembayaran yang telah Anda lakukan')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
                
            Stat::make('Tagihan Berikutnya', $upcomingInvoiceValue)
                ->description($upcomingInvoiceDescription)
                ->descriptionIcon('heroicon-m-calendar')
                ->color($upcomingInvoice ? 'warning' : 'success'),
        ];
    }
}

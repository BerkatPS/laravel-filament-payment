<?php

namespace App\Filament\FinancePages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\FinanceWidgets\FinanceStatsOverview::class,
        ];
    }
    
    public function getColumns(): int|array
    {
        return 2;
    }
    
    public function getTitle(): string
    {
        return 'Dashboard Keuangan';
    }
    
    public function getSubheading(): string
    {
        return 'Selamat datang, ' . Auth::user()->name . '! Anda login sebagai Petugas Keuangan.';
    }
}

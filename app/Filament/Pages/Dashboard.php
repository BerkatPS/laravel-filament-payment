<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;
use App\Filament\Widgets\AdminStatsOverview;
use App\Filament\Widgets\LatestTransactions;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected function getHeaderWidgets(): array
    {
        return [
            AdminStatsOverview::class,
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [
            LatestTransactions::class,
        ];
    }
    
    public function getColumns(): int|array
    {
        return 1;
    }
    
    public function getTitle(): string
    {
        return 'Dashboard Admin';
    }
    
    public function getSubheading(): string
    {
        return 'Selamat datang, ' . Auth::user()->name . '! Anda login sebagai Administrator.';
    }
}

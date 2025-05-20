<?php

namespace App\Filament\CustomerPages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\CustomerWidgets\CustomerStatsOverview::class,
        ];
    }
    
    public function getColumns(): int|array
    {
        return 2;
    }
    
    public function getTitle(): string
    {
        return 'Portal Pelanggan';
    }
    
    public function getSubheading(): string
    {
        return 'Selamat datang, ' . Auth::user()->name . '! Panel ini memungkinkan Anda melihat dan membayar tagihan.';
    }
}

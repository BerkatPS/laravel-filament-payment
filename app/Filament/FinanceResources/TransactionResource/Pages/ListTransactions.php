<?php

namespace App\Filament\FinanceResources\TransactionResource\Pages;

use App\Filament\FinanceResources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\FinanceWidgets\TransactionStats;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            TransactionStats::class,
        ];
    }
}

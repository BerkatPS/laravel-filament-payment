<?php

namespace App\Filament\CustomerResources\TransactionResource\Pages;

use App\Filament\CustomerResources\TransactionResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Pembayaran'),
        ];
    }
}

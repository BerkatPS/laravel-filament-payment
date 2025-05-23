<?php

namespace App\Filament\CustomerResources\InvoiceResource\Pages;

use App\Filament\CustomerResources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;
    
    protected function getHeaderActions(): array
    {
        return [];
    }
}

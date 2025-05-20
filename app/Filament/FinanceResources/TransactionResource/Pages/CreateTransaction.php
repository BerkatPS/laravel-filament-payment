<?php

namespace App\Filament\FinanceResources\TransactionResource\Pages;

use App\Filament\FinanceResources\TransactionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['transaction_number'] = 'TRX-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        $data['transaction_code'] = 'TC-' . strtoupper(Str::random(8));
        $data['payment_date'] = now();
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

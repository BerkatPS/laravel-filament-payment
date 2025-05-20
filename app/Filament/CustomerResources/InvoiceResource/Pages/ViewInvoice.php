<?php

namespace App\Filament\CustomerResources\InvoiceResource\Pages;

use App\Filament\CustomerResources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('bayar')
                ->label('Bayar Sekarang')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->url(fn () => route('customer.payment.create', ['invoice' => $this->record->id]))
                ->visible(fn () => in_array($this->record->status, ['draft', 'sent', 'overdue'])),
            Actions\Action::make('print')
                ->label('Cetak Invoice')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('invoices.print', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}

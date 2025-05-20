<?php

namespace App\Filament\FinanceResources\InvoiceResource\Pages;

use App\Filament\FinanceResources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('markAsPaid')
                ->label('Tandai Dibayar')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->status = 'paid';
                    $this->record->save();
                    $this->notify('success', 'Invoice telah ditandai sebagai dibayar');
                })
                ->visible(fn () => in_array($this->record->status, ['draft', 'sent', 'overdue'])),
            Actions\Action::make('print')
                ->label('Cetak Invoice')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('invoices.print', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}

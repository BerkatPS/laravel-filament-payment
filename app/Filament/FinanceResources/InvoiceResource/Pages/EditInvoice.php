<?php

namespace App\Filament\FinanceResources\InvoiceResource\Pages;

use App\Filament\FinanceResources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
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
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

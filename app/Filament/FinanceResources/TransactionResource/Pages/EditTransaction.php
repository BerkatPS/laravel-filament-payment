<?php

namespace App\Filament\FinanceResources\TransactionResource\Pages;

use App\Filament\FinanceResources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function afterSave(): void
    {
        $record = $this->getRecord();
        
        // Jika status transaksi diubah menjadi completed, update status invoice jika diperlukan
        if ($record->status === 'completed' && $record->invoice) {
            $invoice = $record->invoice;
            $remainingAmount = $invoice->calculateRemainingAmount();
            
            if ($remainingAmount <= 0) {
                $invoice->status = 'paid';
                $invoice->save();
            }
        }
        
        // Jika status berubah menjadi completed dan sebelumnya pending, kirim notifikasi
        if ($record->status === 'completed' && $record->getOriginal('status') === 'pending') {
            if ($record->customer && $record->customer->user) {
                $record->customer->user->notify(new \App\Notifications\PaymentApproved($record));
            }
        }
        
        // Jika status berubah menjadi failed dan sebelumnya pending, kirim notifikasi
        if ($record->status === 'failed' && $record->getOriginal('status') === 'pending') {
            if ($record->customer && $record->customer->user) {
                $record->customer->user->notify(new \App\Notifications\PaymentRejected($record));
            }
        }
    }
}

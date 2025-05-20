<?php

namespace App\Filament\CustomerResources\TransactionResource\Pages;

use App\Filament\CustomerResources\TransactionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use Filament\Notifications\Notification;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate transaction number and code
        $data['transaction_number'] = 'TRX-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        $data['transaction_code'] = 'TC-' . strtoupper(Str::random(8));
        
        // Get customer ID from auth user
        $customerId = Customer::where('user_id', Auth::id())->first()->id;
        $data['customer_id'] = $customerId;
        
        // Set default status to pending
        $data['status'] = 'pending';
        
        // Set payment date to now
        $data['payment_date'] = now();
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function afterCreate(): void
    {
        // Notify customer that payment has been submitted and is awaiting verification
        Notification::make()
            ->title('Pembayaran Berhasil Ditambahkan')
            ->body('Pembayaran Anda telah berhasil ditambahkan dan sedang menunggu verifikasi dari admin.')
            ->success()
            ->send();
            
        // Update invoice status to reflect payment is in process
        $transaction = $this->record;
        if ($transaction->invoice) {
            $invoice = $transaction->invoice;
            if ($invoice->status === 'draft') {
                $invoice->status = 'sent';
                $invoice->save();
            }
        }
    }
}

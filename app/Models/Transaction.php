<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\EncryptionService;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transaction_code',
        'transaction_number',
        'invoice_id',
        'customer_id',
        'amount',
        'payment_method',
        'reference_number',
        'payment_proof',
        'status',
        'payment_reference',
        'payment_details',
        'payment_date',
        'notes',
        'is_manual',
        'refunded_transaction_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'payment_details' => 'json',
        'is_manual' => 'boolean',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    
    public function refundedTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'refunded_transaction_id');
    }

    // Check if transaction is completed successfully
    public function isSuccessful(): bool
    {
        return $this->status === 'completed';
    }

    // Check if transaction can be refunded
    public function canBeRefunded(): bool
    {
        return $this->status === 'completed' && 
               $this->payment_method !== 'cash' && 
               now()->diffInDays($this->payment_date) <= 30;
    }

    // Process a refund
    public function refund(): bool
    {
        if (!$this->canBeRefunded()) {
            return false;
        }

        $this->status = 'refunded';
        $this->save();
        
        // Update invoice status
        if ($this->invoice) {
            $this->invoice->updatePaymentStatus();
        }
        
        // Log the refund activity
        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties([
                'transaction_number' => $this->transaction_number,
                'amount' => $this->amount,
                'payment_method' => $this->payment_method
            ])
            ->log('Transaction refunded');

        return true;
    }

    // Get formatted amount
    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->amount, 2, ',', '.');
    }

    // Get payment method in human readable format
    public function getPaymentMethodTextAttribute()
    {
        return match($this->payment_method) {
            'credit_card' => 'Credit Card',
            'debit_card' => 'Debit Card',
            'bank_transfer' => 'Bank Transfer',
            'e_wallet' => 'E-Wallet',
            'qris' => 'QRIS',
            'cash' => 'Cash',
            'paypal' => 'PayPal',
            default => $this->payment_method,
        };
    }

    // Get payment details with sensitive info masked
    public function getMaskedPaymentDetailsAttribute()
    {
        if (!$this->payment_details) {
            return null;
        }

        $details = json_decode($this->getRawOriginal('payment_details'), true);
        
        if (isset($details['card_number'])) {
            $details['card_number'] = $this->maskCardNumber($details['card_number']);
        }
        
        if (isset($details['security_code'])) {
            $details['security_code'] = '***';
        }
        
        return $details;
    }

    // Mask card number to show only last 4 digits
    protected function maskCardNumber($cardNumber)
    {
        try {
            $encryptionService = app(EncryptionService::class);
            $decrypted = $encryptionService->decrypt($cardNumber);
            
            return str_repeat('*', strlen($decrypted) - 4) . substr($decrypted, -4);
        } catch (\Exception $e) {
            return '****************';
        }
    }
}

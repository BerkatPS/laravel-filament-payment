<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'amount',
        'tax_amount',
        'discount_amount',
        'final_amount',
        'notes',
        'status',
        'due_date',
        'issue_date',
        'discount_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'due_date' => 'date',
        'issue_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Check if invoice is paid
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    // Check if invoice is overdue
    public function isOverdue(): bool
    {
        return $this->status === 'overdue' || 
               ($this->status !== 'paid' && $this->status !== 'cancelled' && now()->greaterThan($this->due_date));
    }

    // Calculate if invoice is paid in full from transactions
    public function calculatePaidAmount(): float
    {
        return $this->transactions()
            ->where('status', 'completed')
            ->sum('amount');
    }

    // Calculate remaining amount to be paid
    public function calculateRemainingAmount(): float
    {
        $paidAmount = $this->calculatePaidAmount();
        return max(0, $this->final_amount - $paidAmount);
    }

    // Update status based on payments
    public function updatePaymentStatus(): void
    {
        if ($this->status === 'cancelled') {
            return;
        }

        $remainingAmount = $this->calculateRemainingAmount();

        if ($remainingAmount <= 0) {
            $this->status = 'paid';
        } else if (now()->greaterThan($this->due_date)) {
            $this->status = 'overdue';
        }

        $this->save();
    }

    // Get formatted final amount
    public function getFormattedFinalAmountAttribute()
    {
        return 'Rp ' . number_format($this->final_amount, 2, ',', '.');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'price',
        'billing_cycle',
        'is_active',
        'duration_days',
        'auto_renewal',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'auto_renewal' => 'boolean',
        'duration_days' => 'integer',
    ];

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class)
            ->withPivot('start_date', 'end_date', 'price', 'status', 'auto_renewal', 'next_billing_date')
            ->withTimestamps();
    }

    public function discounts(): HasMany
    {
        // Hapus pengurutan pada level relasi supaya Filament bisa mengontrol sepenuhnya
        return $this->hasMany(Discount::class);
    }

    public function activeDiscounts()
    {
        return $this->discounts()
            ->where('is_active', true)
            ->where(function($query) {
                $query->where('valid_until', '>=', now())
                      ->orWhereNull('valid_until');
            });
    }
    
    // Get formatted price
    public function getFormattedPriceAttribute()
    {
        return 'Rp ' . number_format($this->price, 2, ',', '.');
    }
    
    // Get billing cycle in human readable format
    public function getBillingCycleTextAttribute()
    {
        return match($this->billing_cycle) {
            'one_time' => 'One Time Payment',
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly (3 Months)',
            'yearly' => 'Yearly',
            default => $this->billing_cycle,
        };
    }
}

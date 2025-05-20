<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    use HasFactory, SoftDeletes;

    // Menonaktifkan perilaku sorting default Filament
    public $sortable = false;

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'max_usage',
        'usage_count',
        'valid_from',
        'valid_until',
        'is_active',
        'service_id',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'max_usage' => 'integer',
        'usage_count' => 'integer',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    // Check if discount is currently valid
    public function isValid(): bool
    {
        // Check if active
        if (!$this->is_active) {
            return false;
        }

        // Check valid dates
        $now = now();
        if ($now < $this->valid_from) {
            return false;
        }

        if ($this->valid_until && $now > $this->valid_until) {
            return false;
        }

        // Check usage count
        if ($this->max_usage && $this->usage_count >= $this->max_usage) {
            return false;
        }

        return true;
    }

    // Calculate discount amount based on order total
    public function calculateDiscount($orderAmount): float
    {
        if (!$this->isValid()) {
            return 0;
        }

        // Check minimum order amount
        if ($this->min_order_amount && $orderAmount < $this->min_order_amount) {
            return 0;
        }

        $discountAmount = 0;

        if ($this->type === 'percentage') {
            $discountAmount = $orderAmount * ($this->value / 100);
        } else { // fixed_amount
            $discountAmount = $this->value;
        }

        // Apply maximum discount cap if set
        if ($this->max_discount_amount && $discountAmount > $this->max_discount_amount) {
            $discountAmount = $this->max_discount_amount;
        }

        return $discountAmount;
    }

    // Increment usage count
    public function incrementUsage(): void
    {
        $this->usage_count++;
        $this->save();
    }
}

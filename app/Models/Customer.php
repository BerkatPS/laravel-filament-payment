<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\EncryptionService;
use App\Models\User;
use App\Models\Service;
use App\Models\Invoice;
use App\Models\Transaction;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'customer_code',
        'national_id_number',
        'date_of_birth',
        'status',
        'last_activity',
        'user_id',
    ];

    protected $hidden = [
        'national_id_number',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'last_activity' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)
            ->withPivot('start_date', 'end_date', 'price', 'status', 'auto_renewal', 'next_billing_date')
            ->withTimestamps();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function activeServices()
    {
        return $this->services()->wherePivot('status', 'active');
    }

    // Accessor to get masked national ID
    public function getNationalIdNumberAttribute($value)
    {
        if (!$value) return null;
        
        try {
            $encryptionService = app(EncryptionService::class);
            $decrypted = $encryptionService->decrypt($value);
            
            // Return only last 4 characters visible
            $length = strlen($decrypted);
            return str_repeat('*', $length - 4) . substr($decrypted, -4);
        } catch (\Exception $e) {
            return '****************';
        }
    }

    // Mutator to encrypt national ID
    public function setNationalIdNumberAttribute($value)
    {
        if (!$value) {
            $this->attributes['national_id_number'] = null;
            return;
        }
        
        $encryptionService = app(EncryptionService::class);
        $this->attributes['national_id_number'] = $encryptionService->encrypt($value);
    }
}

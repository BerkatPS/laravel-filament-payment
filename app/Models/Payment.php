<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Services\EncryptionService;

class Payment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'transaction_id',
        'customer_name',
        'payment_method',
        'amount',
        'status',
        'description',
        'payment_date',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Enkripsi data sensitif saat menyimpan
    public function setCustomerNameAttribute($value)
    {
        $encryptionService = app()->make(EncryptionService::class);
        $this->attributes['customer_name'] = $encryptionService->encrypt($value);
    }

    // Dekripsi data sensitif ketika mengambil
    public function getCustomerNameAttribute($value)
    {
        $encryptionService = app()->make(EncryptionService::class);
        return $encryptionService->decrypt($value);
    }

    // Demikian juga dengan deskripsi
    public function setDescriptionAttribute($value)
    {
        if (!empty($value)) {
            $encryptionService = app()->make(EncryptionService::class);
            $this->attributes['description'] = $encryptionService->encrypt($value);
        } else {
            $this->attributes['description'] = null;
        }
    }

    public function getDescriptionAttribute($value)
    {
        if (!empty($value)) {
            $encryptionService = app()->make(EncryptionService::class);
            return $encryptionService->decrypt($value);
        }
        return $value;
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        $url = url(route('password.reset', [
            'token' => $token,
            'email' => $this->email
        ], false));

        // Log this security event for audit purposes
        \Illuminate\Support\Facades\Log::info('Password reset requested', [
            'user_id' => $this->id,
            'email' => $this->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $this->notify(new \Illuminate\Auth\Notifications\ResetPassword($token));
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole($role)
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Semua pengguna dengan peran admin, finance, atau customer dapat mengakses panel
        return $this->hasRole('admin') || $this->hasRole('finance') || $this->hasRole('customer');
    }

    /**
     * Cek apakah pengguna adalah admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Cek apakah pengguna adalah finance
     */
    public function isFinance(): bool
    {
        return $this->hasRole('finance');
    }

    /**
     * Cek apakah pengguna adalah customer
     */
    public function isCustomer(): bool
    {
        return $this->hasRole('customer');
    }
    
    /**
     * Get the customer associated with the user.
     */
    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }
}

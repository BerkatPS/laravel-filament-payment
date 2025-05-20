<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Semua peran dapat melihat daftar invoice tetapi dengan filter berbeda
        return $user->hasRole('admin') || $user->hasRole('finance') || $user->hasRole('customer');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // Customer hanya dapat melihat invoice mereka sendiri
        if ($user->hasRole('customer')) {
            // Periksa apakah user memiliki relasi customer 
            if (property_exists($user, 'customer') && $user->customer !== null) {
                return $invoice->customer_id === $user->customer->id;
            }
            
            // Jika tidak ada relasi customer, gunakan user_id sebagai fallback
            return $invoice->user_id === $user->id;
        }
        
        // Admin dan Finance dapat melihat semua invoice
        return $user->hasRole('admin') || $user->hasRole('finance');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Hanya admin dan finance yang dapat membuat invoice baru
        return $user->hasRole('admin') || $user->hasRole('finance');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        // Hanya admin dan finance yang dapat mengupdate invoice
        return $user->hasRole('admin') || $user->hasRole('finance');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        // Hanya admin yang dapat menghapus invoice
        return $user->hasRole('admin');
    }
    
    /**
     * Determine whether the user can mark invoice as paid.
     */
    public function markAsPaid(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('admin') || $user->hasRole('finance');
    }
}

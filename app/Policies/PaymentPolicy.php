<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('finance');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->hasRole('admin') || $user->hasRole('finance');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('finance');
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, Payment $payment): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Payment $payment): bool
    {
        return $user->hasRole('admin');
    }
}

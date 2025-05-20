<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;

class HashingService
{
    /**
     * Hash a value that needs to be stored securely but doesn't need to be retrieved
     * in its original form (unlike encryption which is reversible).
     *
     * @param string $value
     * @return string|null
     */
    public function hash($value)
    {
        if (empty($value)) {
            return $value;
        }

        return Hash::make($value);
    }

    /**
     * Check if a plain value matches a hashed value
     *
     * @param string $plainValue
     * @param string $hashedValue
     * @return bool
     */
    public function check($plainValue, $hashedValue)
    {
        if (empty($plainValue) || empty($hashedValue)) {
            return false;
        }

        return Hash::check($plainValue, $hashedValue);
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class EncryptionService
{
    public function encrypt($value)
    {
        if (empty($value)) {
            return $value;
        }

        return Crypt::encryptString($value);
    }

    public function decrypt($value)
    {
        if (empty($value)) {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            // Log the decryption error for security audit
            Log::error('Decryption failed for sensitive data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Return null instead of potentially encrypted data
            return null;
        }
    }
}

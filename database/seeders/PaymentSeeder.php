<?php

namespace Database\Seeders;

use App\Models\Payment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Array of payment methods
        $paymentMethods = ['Credit Card', 'Debit Card', 'Bank Transfer', 'E-Wallet', 'QRIS', 'PayPal', 'Cash'];
        
        // Array of status options
        $statusOptions = ['pending', 'completed', 'failed', 'refunded'];
        
        // Payment descriptions
        $descriptions = [
            'Monthly subscription payment',
            'Product purchase',
            'Service fee',
            'Consultation fee',
            'Membership renewal',
            'Premium upgrade',
            'One-time payment',
            'Installment payment',
            'Recurring payment',
            'General transaction', // Replacing null with a default value
        ];

        for ($i = 1; $i <= 50; $i++) {
            // Calculate a random date within the past year
            $daysAgo = rand(0, 365);
            $paymentDate = Carbon::now()->subDays($daysAgo);
            
            // Create a random amount between 10,000 and 2,000,000
            $amount = rand(10000, 2000000) / 100;
            
            // Higher chance of 'completed' status
            $randomStatus = rand(1, 100);
            if ($randomStatus <= 70) {
                $status = 'completed';
            } elseif ($randomStatus <= 85) {
                $status = 'pending';
            } elseif ($randomStatus <= 95) {
                $status = 'failed';
            } else {
                $status = 'refunded';
            }
            
            // Create a unique transaction ID with format: INV-{YearMonth}-{RandomNumber}
            $transactionId = 'INV-' . $paymentDate->format('Ym') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            Payment::create([
                'transaction_id' => $transactionId,
                'customer_name' => 'Customer ' . $i . ' ' . Str::random(5),
                'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                'amount' => $amount,
                'status' => $status,
                'description' => $descriptions[array_rand($descriptions)],
                'payment_date' => $paymentDate,
            ]);
        }
        
        $this->command->info('50 payment records have been created successfully!');
    }
}

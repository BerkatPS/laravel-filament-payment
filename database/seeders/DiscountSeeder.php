<?php

namespace Database\Seeders;

use App\Models\Discount;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $discounts = [
            [
                'name' => 'Early Bird Discount',
                'code' => 'EARLY25',
                'description' => 'Diskon 25% untuk pemesanan awal.',
                'type' => 'percentage',
                'value' => 25.00,
                'min_order_amount' => 1000000.00,
                'max_discount_amount' => 1000000.00,
                'max_usage' => 100,
                'usage_count' => 0,
                'valid_from' => now(),
                'valid_until' => now()->addMonths(3),
                'is_active' => true,
                'service_id' => 2, // Premium Website Package
            ],
            [
                'name' => 'New Customer',
                'code' => 'WELCOME10',
                'description' => 'Diskon 10% untuk pelanggan baru.',
                'type' => 'percentage',
                'value' => 10.00,
                'min_order_amount' => 500000.00,
                'max_discount_amount' => 500000.00,
                'max_usage' => 1,
                'usage_count' => 0,
                'valid_from' => now(),
                'valid_until' => now()->addYear(),
                'is_active' => true,
                'service_id' => null, // Untuk semua layanan
            ],
            [
                'name' => 'Seasonal Promo',
                'code' => 'SUMMER30',
                'description' => 'Diskon musim panas 30% untuk semua layanan.',
                'type' => 'percentage',
                'value' => 30.00,
                'min_order_amount' => 1000000.00,
                'max_discount_amount' => 2000000.00,
                'max_usage' => 50,
                'usage_count' => 0,
                'valid_from' => now(),
                'valid_until' => now()->addMonths(2),
                'is_active' => true,
                'service_id' => null, // Untuk semua layanan
            ],
            [
                'name' => 'Fixed Discount',
                'code' => 'FLAT500K',
                'description' => 'Potongan tetap Rp 500.000 untuk pembelian layanan e-commerce.',
                'type' => 'fixed_amount',
                'value' => 500000.00,
                'min_order_amount' => 5000000.00,
                'max_discount_amount' => 500000.00,
                'max_usage' => 20,
                'usage_count' => 0,
                'valid_from' => now(),
                'valid_until' => now()->addMonths(6),
                'is_active' => true,
                'service_id' => 3, // E-commerce Website
            ],
            [
                'name' => 'Loyalty Program',
                'code' => 'LOYAL15',
                'description' => 'Diskon 15% untuk pelanggan yang memperpanjang layanan.',
                'type' => 'percentage',
                'value' => 15.00,
                'min_order_amount' => 500000.00,
                'max_discount_amount' => 750000.00,
                'max_usage' => 200,
                'usage_count' => 0,
                'valid_from' => now(),
                'valid_until' => now()->addYear(),
                'is_active' => true,
                'service_id' => null, // Untuk semua layanan
            ],
        ];

        foreach ($discounts as $discountData) {
            // Gunakan firstOrCreate untuk menghindari duplikasi berdasarkan kode diskon
            Discount::firstOrCreate(
                ['code' => $discountData['code']],
                $discountData
            );
        }
    }
}

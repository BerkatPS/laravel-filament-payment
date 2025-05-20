<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'Basic Website Package',
                'code' => 'WEB-BASIC',
                'description' => 'Paket website dasar dengan 5 halaman statis.',
                'price' => 2500000.00,
                'billing_cycle' => 'one_time',
                'features' => json_encode([
                    '5 halaman statis',
                    'Design responsif',
                    'Form kontak',
                    'Integrasi Google Maps',
                    'Support 1 bulan'
                ]),
                'is_active' => true,
                'duration_days' => 30,
                'auto_renewal' => false,
            ],
            [
                'name' => 'Premium Website Package',
                'code' => 'WEB-PREMIUM',
                'description' => 'Paket website premium dengan 10 halaman dan CMS.',
                'price' => 5000000.00,
                'billing_cycle' => 'one_time',
                'features' => json_encode([
                    '10 halaman statis',
                    'Design responsif',
                    'Form kontak',
                    'Integrasi Google Maps',
                    'Content Management System',
                    'Basic SEO',
                    'Support 3 bulan'
                ]),
                'is_active' => true,
                'duration_days' => 90,
                'auto_renewal' => false,
            ],
            [
                'name' => 'E-commerce Website',
                'code' => 'WEB-ECOMMERCE',
                'description' => 'Paket website e-commerce lengkap dengan payment gateway.',
                'price' => 10000000.00,
                'billing_cycle' => 'one_time',
                'features' => json_encode([
                    'Design responsif',
                    'Unlimited product listing',
                    'Shopping cart',
                    'Payment gateway integration',
                    'Admin dashboard',
                    'Customer account management',
                    'Order tracking',
                    'Support 6 bulan'
                ]),
                'is_active' => true,
                'duration_days' => 180,
                'auto_renewal' => false,
            ],
            [
                'name' => 'Web Hosting Basic',
                'code' => 'HOST-BASIC',
                'description' => 'Paket hosting basic untuk website personal.',
                'price' => 500000.00,
                'billing_cycle' => 'yearly',
                'features' => json_encode([
                    '5GB SSD Storage',
                    '10GB Bandwidth',
                    '5 Email accounts',
                    'cPanel Access',
                    '24/7 Support'
                ]),
                'is_active' => true,
                'duration_days' => 365,
                'auto_renewal' => true,
            ],
            [
                'name' => 'Web Hosting Business',
                'code' => 'HOST-BIZ',
                'description' => 'Paket hosting business untuk website perusahaan.',
                'price' => 1500000.00,
                'billing_cycle' => 'yearly',
                'features' => json_encode([
                    '20GB SSD Storage',
                    'Unlimited Bandwidth',
                    'Unlimited Email accounts',
                    'cPanel Access',
                    'SSL Certificate',
                    'Daily Backup',
                    '24/7 Priority Support'
                ]),
                'is_active' => true,
                'duration_days' => 365,
                'auto_renewal' => true,
            ],
            [
                'name' => 'SEO Package Monthly',
                'code' => 'SEO-MONTHLY',
                'description' => 'Paket SEO bulanan untuk meningkatkan ranking website.',
                'price' => 3000000.00,
                'billing_cycle' => 'monthly',
                'features' => json_encode([
                    'Keyword Research',
                    'On-page Optimization',
                    'Content Creation',
                    'Link Building',
                    'Monthly Report',
                    'Weekly Consultation'
                ]),
                'is_active' => true,
                'duration_days' => 30,
                'auto_renewal' => true,
            ],
        ];

        foreach ($services as $serviceData) {
            if (!Service::where('code', $serviceData['code'])->exists()) {
                Service::create($serviceData);
            }
        }
    }
}

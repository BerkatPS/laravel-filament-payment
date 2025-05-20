<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'admin', 'description' => 'Administrator dengan akses penuh ke sistem'],
            ['name' => 'finance', 'description' => 'Departemen Keuangan untuk mengelola transaksi dan pembayaran'],
            ['name' => 'customer', 'description' => 'Pelanggan yang dapat mengakses informasi tagihan dan pembayaran mereka']
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                ['description' => $role['description']]
            );
        }
    }
}

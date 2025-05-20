<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PaymentSeeder::class,
            UserSeeder::class,
            CustomerSeeder::class,
            ServiceSeeder::class,
            DiscountSeeder::class,
            InvoiceSeeder::class,
            TransactionSeeder::class,
        ]);

        // Create admin user
        $adminRole = Role::where('name', 'admin')->first();

        // Check if admin user already exists
        if (!User::where('email', 'admin@example.com')->exists()) {
            $user = User::factory()->create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
            ]);
            
            $user->roles()->attach($adminRole);
        }

        // Create finance user
        $financeRole = Role::where('name', 'finance')->first();
        
        // Check if finance user already exists
        if (!User::where('email', 'finance@example.com')->exists()) {
            $user = User::factory()->create([
                'name' => 'Finance User',
                'email' => 'finance@example.com',
            ]);
            
            $user->roles()->attach($financeRole);
        }

        // Create regular user
        $userRole = Role::where('name', 'user')->first();
        
        // Check if regular user already exists
        if (!User::where('email', 'user@example.com')->exists()) {
            $user = User::factory()->create([
                'name' => 'Regular User',
                'email' => 'user@example.com',
            ]);
            
            $user->roles()->attach($userRole);
        }
    }
}

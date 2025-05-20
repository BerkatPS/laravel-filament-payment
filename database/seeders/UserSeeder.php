<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all roles
        $adminRole = Role::where('name', 'admin')->first();
        $financeRole = Role::where('name', 'finance')->first();
        $customerRole = Role::where('name', 'customer')->first();
        
        // Create admin user
        $this->createUserWithRole('Admin User', 'admin@example.com', $adminRole);
        
        // Create finance user
        $this->createUserWithRole('Finance User', 'finance@example.com', $financeRole);
        
        // Create demo customer user
        $this->createUserWithRole('Customer Demo', 'customer@example.com', $customerRole);
        
        // Create additional users for testing
        $this->createUserWithRole('John Doe', 'john@example.com', $customerRole);
        $this->createUserWithRole('Jane Smith', 'jane@example.com', $customerRole);
        $this->createUserWithRole('Budi Santoso', 'budi@example.com', $customerRole);
        $this->createUserWithRole('Siti Rahma', 'siti@example.com', $customerRole);
        $this->createUserWithRole('Ahmad Hidayat', 'ahmad@example.com', $customerRole);
    }
    
    private function createUserWithRole($name, $email, $role)
    {
        // Check if user already exists
        if (!User::where('email', $email)->exists()) {
            $user = User::factory()->create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt('password'),
            ]);
            
            if ($role) {
                $user->roles()->attach($role);
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customerRole = Role::where('name', 'customer')->first();
        
        // Create John Doe
        $this->createCustomerWithUser(
            'John Doe', 
            'john@example.com', 
            'Jl. Sudirman No. 123, Jakarta Pusat', 
            '081234567890',
            $customerRole
        );
        
        // Create Jane Smith
        $this->createCustomerWithUser(
            'Jane Smith', 
            'jane@example.com', 
            'Jl. Gatot Subroto No. 456, Jakarta Selatan', 
            '082345678901',
            $customerRole
        );
        
        // Create Budi Santoso
        $this->createCustomerWithUser(
            'Budi Santoso', 
            'budi@example.com', 
            'Jl. Ahmad Yani No. 789, Bandung', 
            '083456789012',
            $customerRole
        );
        
        // Create Siti Rahma
        $this->createCustomerWithUser(
            'Siti Rahma', 
            'siti@example.com', 
            'Jl. Diponegoro No. 101, Surabaya', 
            '084567890123',
            $customerRole
        );
        
        // Create Ahmad Hidayat
        $this->createCustomerWithUser(
            'Ahmad Hidayat', 
            'ahmad@example.com', 
            'Jl. Pahlawan No. 202, Semarang', 
            '085678901234',
            $customerRole
        );

        // Create demo customer
        $this->createCustomerWithUser(
            'Customer Demo', 
            'customer@example.com', 
            'Jl. Demo No. 111, Demo City', 
            '089999999999',
            $customerRole
        );
    }
    
    private function createCustomerWithUser($name, $email, $address, $phone, $role)
    {
        // Check if user already exists
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $user = User::factory()->create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt('password'),
            ]);
            
            $user->roles()->attach($role);
        }
        
        // Check if customer exists
        $customer = Customer::where('email', $email)->first();
        
        if (!$customer) {
            Customer::create([
                'user_id' => $user->id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'status' => 'active',
            ]);
        }
    }
}

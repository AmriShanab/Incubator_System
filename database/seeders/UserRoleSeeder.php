<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    

        // 2. The Cashier (Front Desk)
        User::updateOrCreate(
            ['email' => 'cashier@sntech.com'],
            [
                'name' => 'Main Cashier',
                'password' => Hash::make('passwor123'),
                'role' => 'cashier',
            ]
        );

        User::updateOrCreate(
            ['email' => 'inventory@sntech.com'],
            [
                'name' => 'Inventory Manager',
                'password' => Hash::make('password123'),
                'role' => 'inventory',
            ]
        );
    }
}
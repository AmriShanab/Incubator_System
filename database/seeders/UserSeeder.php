<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Using DB::table prevents Laravel's model casts from double-hashing your already-hashed password
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@incubator.com'], // Search by this
            [
                'id' => 1,
                'name' => 'Admin',
                'role' => 'admin',
                'password' => '$2y$12$Tfhc7CysToGwl/30oqK6h.amYVJtNRPA5wYYg8mfxg/QIqOfNRhm.',
                'remember_token' => 'KaYGs6hw8ZlQp1427SwRg1MZ2nA8Q4FvUwx6qcHW5wzN4m56PcUFwtlAARy5',
                'email_verified_at' => null,
                'created_at' => null,
                'updated_at' => null,
            ]
        );
    }
}
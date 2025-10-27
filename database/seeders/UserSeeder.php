<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@audiostatistik.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Create sample regular user
        User::create([
            'name' => 'User Demo',
            'email' => 'user@audiostatistik.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'is_active' => true,
        ]);
    }
}

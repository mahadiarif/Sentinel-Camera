<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Camera;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Default Admin
        User::create([
            'name' => 'Sentinel Admin',
            'email' => 'admin@sentinel.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // 2. Create Initial Camera
        Camera::create([
            'name' => 'Gate 1 - Main Entrance',
            'type' => 'usb',
            'source' => '0',
            'location' => 'Main Gate Security Post',
            'status' => 'active',
        ]);

        Camera::create([
            'name' => 'Gate 2 - Vehicle Entry',
            'type' => 'usb',
            'source' => '1',
            'location' => 'West Parking Entrance',
            'status' => 'active',
        ]);
    }
}

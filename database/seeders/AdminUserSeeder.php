<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create super admin user
        $superAdmin = User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => env('ADMIN_EMAIL', 'admin@privasee.ae'),
            'password' => Hash::make(env('ADMIN_PASSWORD', 'password123')),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'is_active' => true,
            'preferred_language' => 'en',
            'data_processing_consent' => true,
        ]);

        $superAdmin->assignRole('super-admin');

        // Create regular admin user
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin2@privasee.ae',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'is_active' => true,
            'preferred_language' => 'en',
            'data_processing_consent' => true,
        ]);

        $admin->assignRole('admin');

        // Create moderator user
        $moderator = User::create([
            'first_name' => 'Content',
            'last_name' => 'Moderator',
            'email' => 'moderator@privasee.ae',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'is_active' => true,
            'preferred_language' => 'en',
            'data_processing_consent' => true,
        ]);

        $moderator->assignRole('moderator');
    }
}
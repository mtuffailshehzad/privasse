<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample users
        $users = [
            [
                'first_name' => 'Sarah',
                'last_name' => 'Ahmed',
                'email' => 'sarah@example.com',
                'phone' => '+971501234567',
                'date_of_birth' => '1990-05-15',
                'nationality' => 'UAE',
                'subscription_type' => 'premium',
                'subscription_status' => 'active',
            ],
            [
                'first_name' => 'Fatima',
                'last_name' => 'Al Zahra',
                'email' => 'fatima@example.com',
                'phone' => '+971502345678',
                'date_of_birth' => '1985-08-22',
                'nationality' => 'UAE',
                'subscription_type' => 'basic',
                'subscription_status' => 'active',
            ],
            [
                'first_name' => 'Aisha',
                'last_name' => 'Mohammed',
                'email' => 'aisha@example.com',
                'phone' => '+971503456789',
                'date_of_birth' => '1992-12-10',
                'nationality' => 'UAE',
                'subscription_type' => 'vip',
                'subscription_status' => 'active',
            ],
            [
                'first_name' => 'Mariam',
                'last_name' => 'Hassan',
                'email' => 'mariam@example.com',
                'phone' => '+971504567890',
                'date_of_birth' => '1988-03-18',
                'nationality' => 'UAE',
                'subscription_type' => null,
                'subscription_status' => 'inactive',
            ],
            [
                'first_name' => 'Layla',
                'last_name' => 'Ali',
                'email' => 'layla@example.com',
                'phone' => '+971505678901',
                'date_of_birth' => '1995-07-25',
                'nationality' => 'UAE',
                'subscription_type' => 'premium',
                'subscription_status' => 'active',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::create([
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'email' => $userData['email'],
                'phone' => $userData['phone'],
                'password' => Hash::make('password123'),
                'date_of_birth' => $userData['date_of_birth'],
                'nationality' => $userData['nationality'],
                'subscription_type' => $userData['subscription_type'],
                'subscription_status' => $userData['subscription_status'],
                'subscription_expires_at' => $userData['subscription_status'] === 'active' ? now()->addMonth() : null,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'is_active' => true,
                'preferred_language' => 'en',
                'marketing_consent' => true,
                'data_processing_consent' => true,
                'last_login_at' => now()->subDays(rand(0, 30)),
            ]);

            $user->assignRole('user');

            // Create subscription record for active users
            if ($userData['subscription_status'] === 'active' && $userData['subscription_type']) {
                $amounts = [
                    'basic' => 99,
                    'premium' => 199,
                    'vip' => 399,
                ];

                Subscription::create([
                    'user_id' => $user->id,
                    'type' => $userData['subscription_type'],
                    'status' => 'active',
                    'amount' => $amounts[$userData['subscription_type']],
                    'currency' => 'AED',
                    'starts_at' => now()->subMonth(),
                    'expires_at' => now()->addMonth(),
                ]);
            }
        }

        // Create additional random users
        User::factory(45)->create()->each(function ($user) {
            $user->assignRole('user');
        });
    }
}
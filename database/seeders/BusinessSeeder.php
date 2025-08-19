<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Business;
use App\Models\User;

class BusinessSeeder extends Seeder
{
    public function run(): void
    {
        // Create verified businesses
        Business::factory(15)->verified()->create();
        
        // Create pending businesses
        Business::factory(8)->pending()->create();
        
        // Create some women-only businesses
        Business::factory(5)->womenOnly()->verified()->create();
        
        // Create featured businesses
        Business::factory(3)->featured()->verified()->create();

        // Create business owner users for some businesses
        $businesses = Business::where('verification_status', 'verified')->take(10)->get();
        
        foreach ($businesses as $business) {
            $user = User::create([
                'first_name' => explode(' ', $business->owner_name)[0] ?? 'Business',
                'last_name' => explode(' ', $business->owner_name)[1] ?? 'Owner',
                'email' => $business->owner_email,
                'phone' => $business->owner_phone,
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'is_active' => true,
                'preferred_language' => 'en',
                'data_processing_consent' => true,
            ]);

            $user->assignRole('business-owner');
            
            // Link business to user (you might need to add owner_id to businesses table)
            // $business->update(['owner_id' => $user->id]);
        }
    }
}
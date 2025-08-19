<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Venue;
use App\Models\Business;
use App\Models\Category;

class VenueSeeder extends Seeder
{
    public function run(): void
    {
        $businesses = Business::where('verification_status', 'verified')->get();
        
        foreach ($businesses as $business) {
            // Each business gets 1-3 venues
            $venueCount = rand(1, 3);
            
            for ($i = 0; $i < $venueCount; $i++) {
                Venue::factory()->create([
                    'business_id' => $business->id,
                    'is_women_only' => $business->is_women_only,
                    'status' => 'approved',
                    'is_active' => true,
                ]);
            }
        }

        // Create some additional venues in Dubai and Abu Dhabi
        Venue::factory(20)->inDubai()->approved()->create();
        Venue::factory(15)->inAbuDhabi()->approved()->create();
        
        // Create some featured venues
        Venue::factory(8)->featured()->create();
        
        // Create some women-only venues
        Venue::factory(12)->womenOnly()->approved()->create();
        
        // Create some pending venues
        Venue::factory(5)->pending()->create();
    }
}
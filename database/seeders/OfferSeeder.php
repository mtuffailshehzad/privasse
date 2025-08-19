<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Offer;
use App\Models\Business;
use App\Models\Venue;

class OfferSeeder extends Seeder
{
    public function run(): void
    {
        $businesses = Business::where('verification_status', 'verified')->get();
        
        foreach ($businesses as $business) {
            $venues = $business->venues()->where('status', 'approved')->get();
            
            // Each business gets 2-5 offers
            $offerCount = rand(2, 5);
            
            for ($i = 0; $i < $offerCount; $i++) {
                $venue = $venues->random();
                
                Offer::factory()->create([
                    'business_id' => $business->id,
                    'venue_id' => $venue->id,
                    'status' => 'approved',
                    'is_active' => true,
                ]);
            }
        }

        // Create some featured offers
        Offer::factory(10)->featured()->create();
        
        // Create some pending offers
        Offer::factory(8)->pending()->create();
    }
}
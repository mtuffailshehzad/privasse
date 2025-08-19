<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class VenueFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->company() . ' ' . $this->faker->randomElement(['Spa', 'Salon', 'Studio', 'Center', 'Boutique']);
        $emirates = ['Abu Dhabi', 'Dubai', 'Sharjah', 'Ajman', 'Umm Al Quwain', 'Ras Al Khaimah', 'Fujairah'];
        $emirate = $this->faker->randomElement($emirates);
        
        // Dubai coordinates range
        $dubaiCoords = [
            'lat_min' => 24.8, 'lat_max' => 25.4,
            'lng_min' => 54.8, 'lng_max' => 55.5
        ];
        
        // Abu Dhabi coordinates range
        $abuDhabiCoords = [
            'lat_min' => 24.0, 'lat_max' => 24.6,
            'lng_min' => 54.0, 'lng_max' => 54.8
        ];
        
        $coords = $emirate === 'Dubai' ? $dubaiCoords : $abuDhabiCoords;

        return [
            'business_id' => Business::factory(),
            'name' => $name,
            'name_ar' => $this->faker->optional()->company(),
            'description' => $this->faker->paragraph(4),
            'description_ar' => $this->faker->optional()->paragraph(3),
            'category_id' => Category::whereNull('parent_id')->inRandomOrder()->first()?->id ?? 1,
            'subcategory_id' => function (array $attributes) {
                return Category::where('parent_id', $attributes['category_id'])->inRandomOrder()->first()?->id;
            },
            'address' => $this->faker->streetAddress(),
            'address_ar' => $this->faker->optional()->streetAddress(),
            'city' => $this->faker->randomElement(['Dubai Marina', 'Downtown Dubai', 'Jumeirah', 'Business Bay', 'DIFC', 'Abu Dhabi Mall', 'Corniche']),
            'emirate' => $emirate,
            'postal_code' => $this->faker->optional()->postcode(),
            'latitude' => $this->faker->randomFloat(6, $coords['lat_min'], $coords['lat_max']),
            'longitude' => $this->faker->randomFloat(6, $coords['lng_min'], $coords['lng_max']),
            'phone' => '+971' . $this->faker->numerify('4########'),
            'email' => $this->faker->optional()->companyEmail(),
            'website' => $this->faker->optional()->url(),
            'instagram' => $this->faker->optional()->userName(),
            'facebook' => $this->faker->optional()->userName(),
            'operating_hours' => [
                'monday' => ['open' => '09:00', 'close' => '21:00'],
                'tuesday' => ['open' => '09:00', 'close' => '21:00'],
                'wednesday' => ['open' => '09:00', 'close' => '21:00'],
                'thursday' => ['open' => '09:00', 'close' => '22:00'],
                'friday' => ['open' => '14:00', 'close' => '22:00'],
                'saturday' => ['open' => '09:00', 'close' => '22:00'],
                'sunday' => ['open' => '09:00', 'close' => '21:00'],
            ],
            'amenities' => $this->faker->randomElements([
                'WiFi', 'Parking', 'Air Conditioning', 'Wheelchair Accessible',
                'Credit Cards Accepted', 'Valet Parking', 'Private Rooms',
                'Refreshments', 'Changing Rooms', 'Lockers', 'Towels Provided'
            ], $this->faker->numberBetween(2, 6)),
            'price_range' => $this->faker->randomElement(['$', '$$', '$$$', '$$$$']),
            'dress_code' => $this->faker->optional()->randomElement(['Casual', 'Smart Casual', 'Formal', 'Modest']),
            'age_restriction' => $this->faker->optional()->randomElement(['18+', '21+', 'All Ages']),
            'is_women_only' => $this->faker->boolean(40),
            'is_featured' => $this->faker->boolean(15),
            'is_active' => $this->faker->boolean(90),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'average_rating' => $this->faker->randomFloat(2, 3.0, 5.0),
            'total_reviews' => $this->faker->numberBetween(0, 150),
            'total_visits' => $this->faker->numberBetween(0, 500),
            'metadata' => [
                'features' => $this->faker->randomElements([
                    'Premium Products', 'Expert Staff', 'Luxury Experience',
                    'Latest Equipment', 'Organic Products', 'Personalized Service'
                ], $this->faker->numberBetween(1, 3)),
            ],
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function womenOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_women_only' => true,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    public function inDubai(): static
    {
        return $this->state(fn (array $attributes) => [
            'emirate' => 'Dubai',
            'city' => $this->faker->randomElement(['Dubai Marina', 'Downtown Dubai', 'Jumeirah', 'Business Bay', 'DIFC']),
            'latitude' => $this->faker->randomFloat(6, 24.8, 25.4),
            'longitude' => $this->faker->randomFloat(6, 54.8, 55.5),
        ]);
    }

    public function inAbuDhabi(): static
    {
        return $this->state(fn (array $attributes) => [
            'emirate' => 'Abu Dhabi',
            'city' => $this->faker->randomElement(['Abu Dhabi Mall', 'Corniche', 'Khalifa City', 'Al Reem Island']),
            'latitude' => $this->faker->randomFloat(6, 24.0, 24.6),
            'longitude' => $this->faker->randomFloat(6, 54.0, 54.8),
        ]);
    }
}
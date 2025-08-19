<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfferFactory extends Factory
{
    public function definition(): array
    {
        $types = ['discount', 'bogo', 'free_item', 'cashback'];
        $type = $this->faker->randomElement($types);
        $discountType = $this->faker->randomElement(['percentage', 'fixed_amount']);
        
        $originalPrice = $this->faker->randomFloat(2, 50, 500);
        $discountValue = $discountType === 'percentage' 
            ? $this->faker->numberBetween(10, 50)
            : $this->faker->randomFloat(2, 10, 100);
            
        $discountedPrice = $discountType === 'percentage'
            ? $originalPrice * (1 - $discountValue / 100)
            : $originalPrice - $discountValue;

        return [
            'business_id' => Business::factory(),
            'venue_id' => function (array $attributes) {
                return Venue::where('business_id', $attributes['business_id'])->inRandomOrder()->first()?->id;
            },
            'title' => $this->faker->randomElement([
                '50% Off First Visit',
                'Buy One Get One Free',
                'Free Consultation',
                'Weekend Special Discount',
                'New Customer Offer',
                'Luxury Treatment Package',
                'Happy Hour Special',
                'Student Discount',
                'Birthday Special',
                'Loyalty Reward'
            ]),
            'title_ar' => $this->faker->optional()->sentence(3),
            'description' => $this->faker->paragraph(2),
            'description_ar' => $this->faker->optional()->paragraph(2),
            'type' => $type,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'original_price' => $originalPrice,
            'discounted_price' => max(0, $discountedPrice),
            'terms_conditions' => $this->faker->paragraph(3),
            'terms_conditions_ar' => $this->faker->optional()->paragraph(2),
            'start_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'end_date' => $this->faker->dateTimeBetween('now', '+3 months'),
            'usage_limit' => $this->faker->optional(70)->numberBetween(10, 100),
            'usage_limit_per_user' => $this->faker->optional(50)->numberBetween(1, 5),
            'used_count' => $this->faker->numberBetween(0, 20),
            'is_active' => $this->faker->boolean(85),
            'is_featured' => $this->faker->boolean(20),
            'visibility_rules' => [
                'subscription_types' => $this->faker->randomElements(['basic', 'premium', 'vip'], $this->faker->numberBetween(1, 3)),
                'new_users_only' => $this->faker->boolean(30),
                'min_age' => $this->faker->optional()->numberBetween(18, 25),
            ],
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'priority' => $this->faker->numberBetween(0, 10),
            'metadata' => [
                'tags' => $this->faker->randomElements(['popular', 'limited-time', 'exclusive', 'new'], $this->faker->numberBetween(0, 2)),
                'category' => $this->faker->randomElement(['beauty', 'wellness', 'dining', 'shopping']),
            ],
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'status' => 'approved',
            'start_date' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'end_date' => $this->faker->dateTimeBetween('now', '+2 months'),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'is_active' => true,
            'status' => 'approved',
            'priority' => $this->faker->numberBetween(7, 10),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $this->faker->dateTimeBetween('-3 months', '-2 months'),
            'end_date' => $this->faker->dateTimeBetween('-2 months', '-1 week'),
            'is_active' => false,
        ]);
    }

    public function limitedUse(): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => $this->faker->numberBetween(5, 20),
            'usage_limit_per_user' => 1,
        ]);
    }

    public function percentageDiscount(): static
    {
        return $this->state(function (array $attributes) {
            $originalPrice = $this->faker->randomFloat(2, 100, 500);
            $discountValue = $this->faker->numberBetween(15, 50);
            
            return [
                'type' => 'discount',
                'discount_type' => 'percentage',
                'discount_value' => $discountValue,
                'original_price' => $originalPrice,
                'discounted_price' => $originalPrice * (1 - $discountValue / 100),
            ];
        });
    }

    public function fixedDiscount(): static
    {
        return $this->state(function (array $attributes) {
            $originalPrice = $this->faker->randomFloat(2, 100, 500);
            $discountValue = $this->faker->randomFloat(2, 20, 100);
            
            return [
                'type' => 'discount',
                'discount_type' => 'fixed_amount',
                'discount_value' => $discountValue,
                'original_price' => $originalPrice,
                'discounted_price' => max(0, $originalPrice - $discountValue),
            ];
        });
    }
}
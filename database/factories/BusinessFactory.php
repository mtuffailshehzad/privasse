<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->company();
        $subscriptionTypes = ['basic', 'premium', 'enterprise'];
        $subscriptionType = $this->faker->randomElement($subscriptionTypes);
        $verificationStatus = $this->faker->randomElement(['pending', 'verified', 'rejected']);

        return [
            'name' => $name,
            'name_ar' => $this->faker->optional()->company(),
            'description' => $this->faker->paragraph(3),
            'description_ar' => $this->faker->optional()->paragraph(2),
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => '+971' . $this->faker->numerify('4########'),
            'website' => $this->faker->optional()->url(),
            'trade_license_number' => $this->faker->unique()->numerify('TL-######'),
            'trade_license_expiry' => $this->faker->dateTimeBetween('now', '+2 years'),
            'owner_name' => $this->faker->name(),
            'owner_email' => $this->faker->unique()->safeEmail(),
            'owner_phone' => '+971' . $this->faker->numerify('5########'),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'verification_status' => $verificationStatus,
            'verification_notes' => $verificationStatus === 'rejected' ? $this->faker->sentence() : null,
            'subscription_type' => $subscriptionType,
            'subscription_status' => $this->faker->randomElement(['active', 'inactive', 'expired']),
            'subscription_expires_at' => $this->faker->dateTimeBetween('now', '+1 year'),
            'commission_rate' => $this->faker->randomFloat(2, 5, 15),
            'is_featured' => $this->faker->boolean(20),
            'is_women_only' => $this->faker->boolean(30),
            'settings' => [
                'auto_approve_reviews' => $this->faker->boolean(),
                'email_notifications' => $this->faker->boolean(80),
                'sms_notifications' => $this->faker->boolean(60),
            ],
            'verified_at' => $verificationStatus === 'verified' ? $this->faker->dateTimeBetween('-6 months', 'now') : null,
            'rejected_at' => $verificationStatus === 'rejected' ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'verified',
            'verified_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'rejected_at' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'pending',
            'verified_at' => null,
            'rejected_at' => null,
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
        ]);
    }
}
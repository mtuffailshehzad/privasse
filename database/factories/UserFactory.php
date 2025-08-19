<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $subscriptionTypes = ['basic', 'premium', 'vip', null];
        $subscriptionType = $this->faker->randomElement($subscriptionTypes);
        $subscriptionStatus = $subscriptionType ? $this->faker->randomElement(['active', 'inactive', 'expired']) : 'inactive';

        return [
            'first_name' => $this->faker->firstName('female'),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '+971' . $this->faker->numerify('5########'),
            'email_verified_at' => $this->faker->boolean(80) ? now() : null,
            'phone_verified_at' => $this->faker->boolean(70) ? now() : null,
            'password' => static::$password ??= Hash::make('password'),
            'date_of_birth' => $this->faker->dateTimeBetween('-50 years', '-18 years'),
            'nationality' => $this->faker->randomElement(['UAE', 'Saudi Arabia', 'Kuwait', 'Qatar', 'Bahrain', 'Oman', 'Egypt', 'Lebanon', 'Jordan']),
            'preferred_language' => $this->faker->randomElement(['en', 'ar']),
            'subscription_type' => $subscriptionType,
            'subscription_status' => $subscriptionStatus,
            'subscription_expires_at' => $subscriptionStatus === 'active' ? now()->addMonth() : null,
            'is_active' => $this->faker->boolean(95),
            'last_login_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'preferences' => [
                'notifications' => [
                    'email' => $this->faker->boolean(80),
                    'sms' => $this->faker->boolean(60),
                    'push' => $this->faker->boolean(90),
                ],
                'privacy' => [
                    'profile_visibility' => $this->faker->randomElement(['public', 'private']),
                    'show_reviews' => $this->faker->boolean(70),
                ],
            ],
            'biometric_enabled' => $this->faker->boolean(30),
            'two_factor_enabled' => $this->faker->boolean(20),
            'marketing_consent' => $this->faker->boolean(60),
            'data_processing_consent' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
            'phone_verified_at' => null,
        ]);
    }

    public function withSubscription(string $type = 'premium'): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_type' => $type,
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addMonth(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'subscription_status' => 'inactive',
        ]);
    }
}
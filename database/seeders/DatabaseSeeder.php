<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            AdminUserSeeder::class,
            CategorySeeder::class,
            UserSeeder::class,
            BusinessSeeder::class,
            VenueSeeder::class,
            OfferSeeder::class,
        ]);
    }
}
<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            UserSeeder::class,
            EventSeeder::class,
            // RegistrationSeeder::class,
            // FlashSaleCSVSeeder::class,
            // EarlyBirdCSVSeeder::class,
            // InvitationCSVSeeder::class,
            // SpecialPriceCSVSeeder::class,
            // RegulerCSVSeeder::class,
            // CampaignSeeder::class,
            // SangasianCSVSeeder::class
            // Add other seeders here
            // Valid
        ]);
    }
}

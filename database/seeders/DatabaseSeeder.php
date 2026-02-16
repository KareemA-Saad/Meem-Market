<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            BranchSeeder::class,
            SliderSeeder::class,
            SectionSeeder::class,
            PartnerSeeder::class,
            AboutSectionSeeder::class,
            CompetitiveFeatureSeeder::class,
            CareerSeeder::class,
            SettingSeeder::class,
            OfferCategorySeeder::class,
            OfferSeeder::class,
        ]);
    }
}

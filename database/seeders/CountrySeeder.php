<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['name_ar' => 'السعودية', 'name_en' => 'Saudi Arabia', 'slug' => 'saudia', 'is_active' => true, 'sort_order' => 1],
            ['name_ar' => 'الكويت', 'name_en' => 'Kuwait', 'slug' => 'kuwait', 'is_active' => true, 'sort_order' => 2],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(['slug' => $country['slug']], $country);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Partner;
use Illuminate\Database\Seeder;

class PartnerSeeder extends Seeder
{
    public function run(): void
    {
        $baseUrl = 'https://meem-market.com/wp-content/uploads/2026/01/';

        $partners = [
            ['name' => 'Alizz Furniture', 'logo' => $baseUrl . 'Alizz-furniture-logo-1024x683.webp'],
            ['name' => 'Awanik', 'logo' => $baseUrl . 'Awanik-logo-1024x683.webp'],
            ['name' => 'Bema', 'logo' => $baseUrl . 'Bema-logo-1024x683.webp'],
            ['name' => 'Clemance', 'logo' => $baseUrl . 'Clemance-logox-1024x683.webp'],
            ['name' => 'Dream Reem', 'logo' => $baseUrl . 'Dream-reem-logo-1024x683.webp'],
            ['name' => 'Electovision', 'logo' => $baseUrl . 'Electovision-logo-1024x683.webp'],
            ['name' => 'Great Shave', 'logo' => $baseUrl . 'Great-shave-logo-1024x683.webp'],
            ['name' => 'Maxi Mooth', 'logo' => $baseUrl . 'Maxi-mooth-logo-1024x683.webp'],
            ['name' => 'Mila', 'logo' => $baseUrl . 'Mila-logo-1024x683.webp'],
            ['name' => 'Rabco', 'logo' => $baseUrl . 'Rabco-logo-1024x683.webp'],
            ['name' => 'Youmma', 'logo' => $baseUrl . 'Youmma-logo-1024x683.png'],
            ['name' => 'Zaina', 'logo' => $baseUrl . 'Zaina-logo-1024x683.webp'],
        ];

        foreach ($partners as $i => $partner) {
            Partner::updateOrCreate(
                ['name' => $partner['name']],
                array_merge($partner, ['sort_order' => $i + 1, 'is_active' => true])
            );
        }
    }
}

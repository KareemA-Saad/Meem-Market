<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Country;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $saudia = Country::where('slug', 'saudia')->first();
        $kuwait = Country::where('slug', 'kuwait')->first();

        $branches = [
            // Saudi Arabia
            [
                'country_id' => $saudia->id,
                'name_ar' => 'الرياض',
                'name_en' => 'Riyadh',
                'slug' => 'riyadh',
                'address' => 'الرياض، السعودية',
                'phone' => '0573666192',
                'unified_phone' => '920010937',
                'google_maps_url' => 'https://maps.app.goo.gl/6bxUpP4DpAsJBUnX7',
                'latitude' => 24.7559565,
                'longitude' => 46.8300541,
                'social_links' => [
                    'whatsapp' => 'https://wa.me/966573666192',
                    'tiktok' => 'https://www.tiktok.com/@meemmarketkw',
                    'snapchat' => 'https://www.snapchat.com/add/meemmarketkw',
                    'facebook' => 'https://www.facebook.com/meemmarketkw1',
                    'instagram' => 'https://www.instagram.com/meemmarketkw',
                    'website' => 'https://meem.market',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'country_id' => $saudia->id,
                'name_ar' => 'الأحساء',
                'name_en' => 'Al Ahsa',
                'slug' => 'ahsa',
                'address' => 'الأحساء، السعودية',
                'phone' => '0551297970',
                'unified_phone' => '920010937',
                'google_maps_url' => 'https://maps.app.goo.gl/sB37oFF8SQtT44B6A',
                'latitude' => 25.4017469,
                'longitude' => 49.5600663,
                'social_links' => [
                    'whatsapp' => 'https://wa.me/966551297970',
                    'tiktok' => 'https://www.tiktok.com/@meemmarketkw',
                    'snapchat' => 'https://www.snapchat.com/add/meemmarketkw',
                    'facebook' => 'https://www.facebook.com/meemmarketkw1',
                    'instagram' => 'https://www.instagram.com/meemmarketkw',
                    'website' => 'https://meem.market',
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],

            // Kuwait
            [
                'country_id' => $kuwait->id,
                'name_ar' => 'القرين',
                'name_en' => 'Al Qareen',
                'slug' => 'qareen',
                'address' => 'القرين، الكويت',
                'phone' => null,
                'unified_phone' => null,
                'google_maps_url' => 'https://maps.app.goo.gl/D4XkDY1qdsVWzy2K7',
                'latitude' => 29.2001864,
                'longitude' => 48.0455266,
                'social_links' => [
                    'whatsapp' => 'https://wa.me/96566107080',
                    'tiktok' => 'https://www.tiktok.com/@meemmarketkw',
                    'snapchat' => 'https://www.snapchat.com/add/meemmarketkw',
                    'facebook' => 'https://www.facebook.com/meemmarketkw1',
                    'instagram' => 'https://www.instagram.com/meemmarketkw',
                    'website' => 'https://meem.market',
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'country_id' => $kuwait->id,
                'name_ar' => 'الريّ',
                'name_en' => 'Al Rai',
                'slug' => 'rai',
                'address' => 'الريّ، الكويت',
                'phone' => '96522281131',
                'unified_phone' => null,
                'google_maps_url' => 'https://maps.app.goo.gl/CeCttgGwFsUYXYti9',
                'latitude' => 29.3117824,
                'longitude' => 47.9436570,
                'social_links' => [
                    'whatsapp' => 'https://wa.me/96560707042',
                    'tiktok' => 'https://www.tiktok.com/@meemmarketkw',
                    'snapchat' => 'https://www.snapchat.com/add/meemmarketkw',
                    'facebook' => 'https://www.facebook.com/meemmarketkw1',
                    'instagram' => 'https://www.instagram.com/meemmarketkw',
                    'website' => 'https://meem.market',
                ],
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'country_id' => $kuwait->id,
                'name_ar' => 'العقيلة',
                'name_en' => 'Al Eqaila',
                'slug' => 'eqaila',
                'address' => 'العقيلة، الكويت',
                'phone' => null,
                'unified_phone' => null,
                'google_maps_url' => 'https://maps.app.goo.gl/XMje6RBXnHJrz1yC6',
                'latitude' => 29.1685159,
                'longitude' => 48.0977462,
                'social_links' => [
                    'whatsapp' => 'https://wa.me/96566107080',
                    'tiktok' => 'https://www.tiktok.com/@meemmarketkw',
                    'snapchat' => 'https://www.snapchat.com/add/meemmarketkw',
                    'facebook' => 'https://www.facebook.com/meemmarketkw1',
                    'instagram' => 'https://www.instagram.com/meemmarketkw',
                    'website' => 'https://meem.market',
                ],
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(['slug' => $branch['slug']], $branch);
        }
    }
}

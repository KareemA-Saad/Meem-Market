<?php

namespace Database\Seeders;

use App\Models\Offer;
use App\Models\OfferCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OfferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find 'Coming Winter Offers' category (extracted slug or Arabic title)
        // Slug was generated from English title 'Coming Winter Offers' -> 'coming-winter-offers'
        $category = OfferCategory::where('slug', Str::slug('Coming Winter Offers'))
            ->orWhere('title', 'عروض الشتاء القادم')
            ->first();

        if (!$category) {
            return;
        }

        $offers = [
            [
                'image' => 'https://meem-market.com/wp-content/uploads/2025/12/الشتاء-القادم-عرض-3.webp',
                'title' => 'Winter Offer 1', // Generic title as none was explicit in view
                'sort_order' => 1,
            ],
            [
                'image' => 'https://meem-market.com/wp-content/uploads/2025/12/الشتاء-القادم-عرض-2.webp',
                'title' => 'Winter Offer 2',
                'sort_order' => 2,
            ],
            [
                'image' => 'https://meem-market.com/wp-content/uploads/2025/12/عرض-الشتاء-القادم-1.jpg',
                'title' => 'Winter Offer 3',
                'sort_order' => 3,
            ],
        ];

        foreach ($offers as $offerData) {
            Offer::updateOrCreate(
                [
                    'offer_category_id' => $category->id,
                    'image' => $offerData['image'],
                ],
                [
                    'title' => $offerData['title'],
                    'is_active' => true,
                    'sort_order' => $offerData['sort_order'],
                ]
            );
        }
    }
}

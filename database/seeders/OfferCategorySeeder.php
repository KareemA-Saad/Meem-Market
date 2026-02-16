<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\OfferCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OfferCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ahsaBranch = Branch::where('name_en', 'Al Ahsa')->orWhere('name_ar', 'الأحساء')->first();

        // Fallback if Al Ahsa not found (should be seeded by BranchSeeder)
        if (!$ahsaBranch) {
            $ahsaBranch = Branch::first();
        }

        if (!$ahsaBranch) {
            return;
        }

        $categories = [
            [
                'title' => 'Late Summer Offers',
                'title_ar' => 'عروض أواخر الصيف',
                'cover_image' => 'https://meem-market.com/wp-content/uploads/2025/12/عروض-أواخر-الصيف-819x1024.webp',
                'start_date' => '2025-10-05',
                'end_date' => '2025-10-08',
                'is_active' => false,
                'sort_order' => 1,
            ],
            [
                'title' => 'Coming Winter Offers',
                'title_ar' => 'عروض الشتاء القادم',
                'cover_image' => 'https://meem-market.com/wp-content/uploads/2025/12/عروض-الشتاء-القادم-888x1024.webp',
                'start_date' => now()->subDays(5)->toDateString(), // Assuming active based on "العرض ساري"
                'end_date' => now()->addDays(10)->toDateString(),
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'National Day Offers',
                'title_ar' => 'عروض اليوم الوطني',
                'cover_image' => 'https://meem-market.com/wp-content/uploads/2025/12/عروض-اليوم-الوطني-820x1024.webp',
                'start_date' => '2025-09-16',
                'end_date' => '2025-09-25',
                'is_active' => false,
                'sort_order' => 3,
            ],
        ];

        foreach ($categories as $categoryData) {
            // Check if title column supports translation or if we should use one language
            // Assuming single language column 'title' based on model view, but seeders often used Arabic content in this project context.
            // Let's use the English key 'title' but put Arabic content if that's the primary language, or stick to English.
            // Earlier seeders used English keys. Let's stick to English for consistency if DB supports it, or Arabic if required.
            // However, extracting Arabic titles above. I will use Arabic extracted titles as the primary title since it's an Arabic site.

            OfferCategory::updateOrCreate(
                [
                    'branch_id' => $ahsaBranch->id,
                    'slug' => Str::slug($categoryData['title']), // Slug from English title for cleaner URLs
                ],
                [
                    'title' => $categoryData['title_ar'], // Using Arabic title as primary
                    'cover_image' => $categoryData['cover_image'],
                    'start_date' => $categoryData['start_date'],
                    'end_date' => $categoryData['end_date'],
                    'is_active' => $categoryData['is_active'],
                    'sort_order' => $categoryData['sort_order'],
                ]
            );
        }
    }
}

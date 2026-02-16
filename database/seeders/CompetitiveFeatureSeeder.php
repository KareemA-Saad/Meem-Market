<?php

namespace Database\Seeders;

use App\Models\CompetitiveFeature;
use Illuminate\Database\Seeder;

class CompetitiveFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            [
                'title' => 'تنوع ونوعية',
                'description' => 'تنوع يلبي جميع الاحتياجات',
                'icon' => 'https://meem-market.com/wp-content/uploads/2025/12/variety-icon.png',
                'sort_order' => 1,
            ],
            [
                'title' => 'جودة أكثر',
                'description' => 'جودة تفوق التوقعات',
                'icon' => 'https://meem-market.com/wp-content/uploads/2025/12/more-quality-icon.png',
                'sort_order' => 2,
            ],
            [
                'title' => 'توفير أكبر',
                'description' => 'أسعار تنافسية ومرنة',
                'icon' => 'https://meem-market.com/wp-content/uploads/2025/12/greater-savings-icon.png',
                'sort_order' => 3,
            ],
        ];

        foreach ($features as $feature) {
            CompetitiveFeature::updateOrCreate(['title' => $feature['title']], $feature);
        }
    }
}

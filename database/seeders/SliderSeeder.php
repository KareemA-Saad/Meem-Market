<?php

namespace Database\Seeders;

use App\Models\Slider;
use Illuminate\Database\Seeder;

class SliderSeeder extends Seeder
{
    public function run(): void
    {
        $sliders = [
            ['title' => 'سوق ميم - الأحساء', 'image' => 'https://meem-market.com/wp-content/uploads/2025/11/meem-a7sa-scaled.webp', 'sort_order' => 1, 'is_active' => true],
            ['title' => 'سوق ميم - الكويت', 'image' => 'https://meem-market.com/wp-content/uploads/2025/11/meem-kw-scaled.webp', 'sort_order' => 2, 'is_active' => true],
            ['title' => 'سوق ميم', 'image' => 'https://meem-market.com/wp-content/uploads/2025/11/hero-banner-imgx.webp', 'sort_order' => 3, 'is_active' => true],
        ];

        foreach ($sliders as $slider) {
            Slider::updateOrCreate(['image' => $slider['image']], $slider);
        }
    }
}

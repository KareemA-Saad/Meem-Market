<?php

namespace Database\Seeders;

use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        $baseUrl = 'https://meem-market.com/wp-content/uploads/2026/01/';

        $sections = [
            ['title' => 'أدوات التجميل', 'image' => $baseUrl . 'قسم-أدوات-التجميل.webp'],
            ['title' => 'الأحذية والشنط', 'image' => $baseUrl . 'قسم-الأحذية-والشنط.webp'],
            ['title' => 'الألعاب والترفيه', 'image' => $baseUrl . 'قسم-الألعاب-والترفية.webp'],
            ['title' => 'الأواني المنزلية', 'image' => $baseUrl . 'قسم-الأواني-المنزلية.webp'],
            ['title' => 'البلاستك والخردوات', 'image' => $baseUrl . 'قسم-البلاستك-والخردوات.webp'],
            ['title' => 'التحف والهدايا', 'image' => $baseUrl . 'قسم-التحف-والهدايا.webp'],
            ['title' => 'الخضروات والفواكه', 'image' => $baseUrl . 'قسم-الخضروات-والفواكة.webp'],
            ['title' => 'العِدد وزينة السيارات', 'image' => $baseUrl . 'قسم-العِدد-وزينة-السيارات.webp'],
            ['title' => 'العطور والبخور', 'image' => $baseUrl . 'قسم-العطور-والبخور.webp'],
            ['title' => 'العناية', 'image' => $baseUrl . 'قسم-العناية.webp'],
            ['title' => 'الفوط', 'image' => $baseUrl . 'قسم-الفوط.webp'],
            ['title' => 'اللوازم الشخصية', 'image' => $baseUrl . 'قسم-اللوازم-الشخصية.webp'],
            ['title' => 'المفروشات', 'image' => $baseUrl . 'قسم-المفروشات.webp'],
            ['title' => 'العناية بالطفل', 'image' => $baseUrl . 'قسم-العناية-بالطفل.webp'],
            ['title' => 'المواد الاستهلاكية', 'image' => $baseUrl . 'قسم-المواد-الاستهلاكية.webp'],
            ['title' => 'المنظفات', 'image' => $baseUrl . 'قسم-المنظفات.webp'],
            ['title' => 'المواد الغذائية', 'image' => $baseUrl . 'قسم-المواد-الغذائية.webp'],
        ];

        foreach ($sections as $i => $section) {
            Section::updateOrCreate(
                ['title' => $section['title']],
                array_merge($section, ['sort_order' => $i + 1, 'is_active' => true])
            );
        }
    }
}

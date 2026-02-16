<?php

namespace Database\Seeders;

use App\Models\Career;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CareerSeeder extends Seeder
{
    public function run(): void
    {
        $careers = [
            [
                'title' => 'بائع',
                'slug' => 'بائع',
                'location' => 'السعودية / الكويت',
                'type' => 'دوام كامل',
                'description' => 'مطلوب بائع للعمل في أسواق ميم. المهام تشمل خدمة العملاء وعرض المنتجات وتنظيم الأقسام.',
                'requirements' => 'خبرة سابقة في المبيعات، مهارات تواصل جيدة، القدرة على العمل ضمن فريق.',
                'is_active' => true,
            ],
            [
                'title' => 'عامل كاشير',
                'slug' => 'عامل-كاشير',
                'location' => 'السعودية / الكويت',
                'type' => 'دوام كامل',
                'description' => 'مطلوب عامل كاشير للعمل في أسواق ميم. المهام تشمل تحصيل المدفوعات وخدمة العملاء عند نقاط البيع.',
                'requirements' => 'خبرة في أنظمة نقاط البيع، دقة في التعامل المالي، مهارات تواصل.',
                'is_active' => true,
            ],
            [
                'title' => 'خدمة عملاء',
                'slug' => 'خدمة-عملاء',
                'location' => 'السعودية / الكويت',
                'type' => 'دوام كامل',
                'description' => 'مطلوب موظف خدمة عملاء للعمل في أسواق ميم. المهام تشمل استقبال العملاء والرد على استفساراتهم.',
                'requirements' => 'مهارات تواصل ممتازة، صبر ولباقة، خبرة سابقة في خدمة العملاء.',
                'is_active' => true,
            ],
            [
                'title' => 'مصمم مونتاج',
                'slug' => 'مصمم-مونتاج',
                'location' => 'السعودية',
                'type' => 'دوام كامل',
                'description' => 'مطلوب مصمم مونتاج للعمل في قسم التسويق بأسواق ميم. المهام تشمل تصميم وتحرير الفيديوهات الترويجية.',
                'requirements' => 'إجادة برامج المونتاج (Premiere, After Effects)، إبداع في التصميم، خبرة سابقة.',
                'is_active' => true,
            ],
        ];

        foreach ($careers as $career) {
            Career::updateOrCreate(['slug' => $career['slug']], $career);
        }
    }
}

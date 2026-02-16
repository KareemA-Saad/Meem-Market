<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['group' => 'general', 'key' => 'site_name', 'value' => 'Meem Market ®'],
            ['group' => 'general', 'key' => 'site_name_ar', 'value' => 'سوق ميم'],
            ['group' => 'general', 'key' => 'company_name', 'value' => 'شركة ميم المتميزة للتجارة'],
            ['group' => 'general', 'key' => 'tagline', 'value' => 'سوق ميم… وجهتك الشاملة للتسوق الذكي'],
            ['group' => 'general', 'key' => 'logo', 'value' => 'https://meem-market.com/wp-content/uploads/2025/11/meem-logo.png'],
            ['group' => 'general', 'key' => 'logo_white', 'value' => 'https://meem-market.com/wp-content/uploads/2025/12/Meem-logox-white.png'],
            ['group' => 'general', 'key' => 'favicon', 'value' => 'https://meem-market.com/wp-content/uploads/2025/11/cropped-favico-512x512-1-32x32.png'],
            ['group' => 'general', 'key' => 'footer_text', 'value' => 'سوق ميم وجهة تسوق متكاملة توفر تنوعاً واسعاً من المنتجات الأسرية المختارة بعناية، في تجربة تسوق تجمع الجودة والراحة وسهولة الاختيار تحت سقف واحد.'],
            ['group' => 'general', 'key' => 'copyright', 'value' => '© 2026 سوق ميم – جميع الحقوق محفوظة.'],
            ['group' => 'general', 'key' => 'developer_name', 'value' => 'كيان سوفت'],
            ['group' => 'general', 'key' => 'developer_url', 'value' => 'https://kyan-soft.com'],

            // Contact
            ['group' => 'contact', 'key' => 'contact_heading', 'value' => 'تواصل معنا'],
            ['group' => 'contact', 'key' => 'contact_subtitle', 'value' => 'نحن هنا لخدمتك والإجابة على استفساراتك'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['group' => $setting['group'], 'key' => $setting['key']],
                $setting
            );
        }
    }
}

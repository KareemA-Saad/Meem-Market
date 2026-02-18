<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserMeta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the default admin user for the CMS layer.
 * Runs AFTER OptionSeeder (roles must exist first).
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@meemmark.com'],
            [
                'name' => 'Admin',
                'login' => 'admin',
                'nicename' => 'admin',
                'display_name' => 'Admin',
                'password' => Hash::make('password'),
                'url' => '',
                'registered_at' => now(),
                'activation_key' => '',
                'status' => 0,
            ],
        );

        // Assign administrator role via user_meta (WP convention)
        UserMeta::updateOrCreate(
            ['user_id' => $admin->id, 'meta_key' => 'wp_capabilities'],
            ['meta_value' => json_encode(['administrator' => true])],
        );

        // Store nicename and other meta
        $defaultMeta = [
            'nickname' => 'admin',
            'first_name' => '',
            'last_name' => '',
            'description' => '',
            'rich_editing' => 'true',
            'syntax_highlighting' => 'true',
            'admin_color' => 'fresh',
            'show_admin_bar_front' => 'true',
        ];

        foreach ($defaultMeta as $key => $value) {
            UserMeta::updateOrCreate(
                ['user_id' => $admin->id, 'meta_key' => $key],
                ['meta_value' => $value],
            );
        }
    }
}

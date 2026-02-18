<?php

namespace Database\Seeders;

use App\Models\Option;
use App\Models\Term;
use App\Models\TermTaxonomy;
use Illuminate\Database\Seeder;

/**
 * Seeds the CMS options table with WordPress default values.
 *
 * This is separate from the existing SettingSeeder which seeds the
 * storefront's `settings` table (group/key/value).
 */
class OptionSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRoles();
        $this->seedDefaultOptions();
        $this->seedDefaultCategory();
    }

    /**
     * Seed WP's 5 default roles with their exact capability maps.
     */
    private function seedRoles(): void
    {
        $roles = [
            'administrator' => [
                'name' => 'Administrator',
                'capabilities' => [
                    'switch_themes' => true,
                    'edit_themes' => true,
                    'activate_plugins' => true,
                    'edit_plugins' => true,
                    'edit_users' => true,
                    'edit_files' => true,
                    'manage_options' => true,
                    'moderate_comments' => true,
                    'manage_categories' => true,
                    'manage_links' => true,
                    'upload_files' => true,
                    'import' => true,
                    'unfiltered_html' => true,
                    'edit_posts' => true,
                    'edit_others_posts' => true,
                    'edit_published_posts' => true,
                    'publish_posts' => true,
                    'edit_pages' => true,
                    'read' => true,
                    'level_10' => true,
                    'level_9' => true,
                    'level_8' => true,
                    'level_7' => true,
                    'level_6' => true,
                    'level_5' => true,
                    'level_4' => true,
                    'level_3' => true,
                    'level_2' => true,
                    'level_1' => true,
                    'level_0' => true,
                    'edit_others_pages' => true,
                    'edit_published_pages' => true,
                    'publish_pages' => true,
                    'delete_pages' => true,
                    'delete_others_pages' => true,
                    'delete_published_pages' => true,
                    'delete_posts' => true,
                    'delete_others_posts' => true,
                    'delete_published_posts' => true,
                    'delete_private_posts' => true,
                    'edit_private_posts' => true,
                    'read_private_posts' => true,
                    'delete_private_pages' => true,
                    'edit_private_pages' => true,
                    'read_private_pages' => true,
                    'delete_users' => true,
                    'create_users' => true,
                    'unfiltered_upload' => true,
                    'edit_dashboard' => true,
                    'update_plugins' => true,
                    'delete_plugins' => true,
                    'install_plugins' => true,
                    'update_themes' => true,
                    'install_themes' => true,
                    'update_core' => true,
                    'list_users' => true,
                    'remove_users' => true,
                    'promote_users' => true,
                    'edit_theme_options' => true,
                    'delete_themes' => true,
                    'export' => true,
                ],
            ],
            'editor' => [
                'name' => 'Editor',
                'capabilities' => [
                    'moderate_comments' => true,
                    'manage_categories' => true,
                    'manage_links' => true,
                    'upload_files' => true,
                    'unfiltered_html' => true,
                    'edit_posts' => true,
                    'edit_others_posts' => true,
                    'edit_published_posts' => true,
                    'publish_posts' => true,
                    'edit_pages' => true,
                    'read' => true,
                    'level_7' => true,
                    'level_6' => true,
                    'level_5' => true,
                    'level_4' => true,
                    'level_3' => true,
                    'level_2' => true,
                    'level_1' => true,
                    'level_0' => true,
                    'edit_others_pages' => true,
                    'edit_published_pages' => true,
                    'publish_pages' => true,
                    'delete_pages' => true,
                    'delete_others_pages' => true,
                    'delete_published_pages' => true,
                    'delete_posts' => true,
                    'delete_others_posts' => true,
                    'delete_published_posts' => true,
                    'delete_private_posts' => true,
                    'edit_private_posts' => true,
                    'read_private_posts' => true,
                    'delete_private_pages' => true,
                    'edit_private_pages' => true,
                    'read_private_pages' => true,
                ],
            ],
            'author' => [
                'name' => 'Author',
                'capabilities' => [
                    'upload_files' => true,
                    'edit_posts' => true,
                    'edit_published_posts' => true,
                    'publish_posts' => true,
                    'read' => true,
                    'level_2' => true,
                    'level_1' => true,
                    'level_0' => true,
                    'delete_posts' => true,
                    'delete_published_posts' => true,
                ],
            ],
            'contributor' => [
                'name' => 'Contributor',
                'capabilities' => [
                    'edit_posts' => true,
                    'read' => true,
                    'level_1' => true,
                    'level_0' => true,
                    'delete_posts' => true,
                ],
            ],
            'subscriber' => [
                'name' => 'Subscriber',
                'capabilities' => [
                    'read' => true,
                    'level_0' => true,
                ],
            ],
        ];

        Option::set('user_roles', $roles, 'yes');
    }

    /**
     * Seed the CMS default options matching WordPress defaults.
     */
    private function seedDefaultOptions(): void
    {
        $defaults = [
            'blogname' => 'MeemMark',
            'blogdescription' => 'Just another site',
            'siteurl' => 'http://localhost:8000',
            'home' => 'http://localhost:8000',
            'admin_email' => 'admin@meemmark.com',
            'date_format' => 'F j, Y',
            'time_format' => 'g:i a',
            'posts_per_page' => '10',
            'default_role' => 'subscriber',
            'timezone_string' => 'Asia/Riyadh',
            'start_of_week' => '1',
            'users_can_register' => '0',
            'default_comment_status' => 'open',
            'blog_public' => '1',
            'show_on_front' => 'posts',
            'thumbnail_size_w' => '150',
            'thumbnail_size_h' => '150',
            'thumbnail_crop' => '1',
            'medium_size_w' => '300',
            'medium_size_h' => '300',
            'large_size_w' => '1024',
            'large_size_h' => '1024',
            'uploads_use_yearmonth_folders' => '1',
            'default_category' => '1',
            'comment_moderation' => '0',
            'require_name_email' => '1',
            'permalink_structure' => '/%postname%/',
            'category_base' => '',
            'tag_base' => '',
        ];

        foreach ($defaults as $name => $value) {
            Option::updateOrCreate(
                ['name' => $name],
                ['value' => $value, 'autoload' => 'yes'],
            );
        }
    }

    /**
     * Seed the default "Uncategorized" category term.
     */
    private function seedDefaultCategory(): void
    {
        $term = Term::firstOrCreate(
            ['slug' => 'uncategorized'],
            ['name' => 'Uncategorized', 'term_group' => 0],
        );

        TermTaxonomy::firstOrCreate(
            ['term_id' => $term->id, 'taxonomy' => 'category'],
            ['description' => '', 'parent' => 0, 'count' => 0],
        );
    }
}

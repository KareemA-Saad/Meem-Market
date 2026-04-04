<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Career;
use App\Models\Comment;
use App\Models\CompetitiveFeature;
use App\Models\ContactMessage;
use App\Models\Country;
use App\Models\Offer;
use App\Models\OfferCategory;
use App\Models\Option;
use App\Models\Partner;
use App\Models\Post;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Slider;
use App\Models\User;
use App\Models\UserMeta;
use App\Services\OptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardAdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/dashboard/stats')->assertStatus(401);
        $this->postJson('/api/v1/admin/dashboard/quick-draft', ['title' => 'x'])->assertStatus(401);
    }

    public function test_dashboard_routes_enforce_capability_checks(): void
    {
        $this->authenticateWithCapabilities(['read']);

        $this->getJson('/api/v1/admin/dashboard/stats')->assertStatus(200);
        $this->postJson('/api/v1/admin/dashboard/quick-draft', ['title' => 'Nope'])->assertStatus(403);
    }

    public function test_dashboard_stats_and_quick_draft_flow(): void
    {
        $this->authenticateWithCapabilities(['read', 'edit_posts', 'manage_options']);

        $country = Country::create([
            'name_ar' => 'ط§ظ„ط³ط¹ظˆط¯ظٹط©',
            'name_en' => 'Saudi Arabia',
            'slug' => 'sa',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $branch = Branch::create([
            'country_id' => $country->id,
            'name_ar' => 'ط§ظ„ط£ط­ط³ط§ط،',
            'name_en' => 'Al Ahsa',
            'slug' => 'ahsa',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $category = OfferCategory::create([
            'branch_id' => $branch->id,
            'title' => 'Summer Offers',
            'slug' => 'summer-offers',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Offer::create([
            'offer_category_id' => $category->id,
            'title' => 'Offer 1',
            'image' => 'https://example.com/offer1.webp',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Offer::create([
            'offer_category_id' => $category->id,
            'title' => 'Offer 2',
            'image' => 'https://example.com/offer2.webp',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        Career::create([
            'title' => 'Cashier',
            'slug' => 'cashier',
            'description' => 'Career description',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        ContactMessage::create([
            'name' => 'John',
            'email' => 'john@example.com',
            'message' => 'Hello',
            'is_read' => false,
        ]);

        Slider::create([
            'title' => 'Hero',
            'image' => 'https://example.com/hero.webp',
            'media_type' => 'image',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Section::create([
            'title' => 'Electronics',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Partner::create([
            'name' => 'Partner A',
            'logo' => 'https://example.com/logo.webp',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        CompetitiveFeature::create([
            'title' => 'More Quality',
            'description' => 'Quality beyond expectations',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Setting::create(['group' => 'general', 'key' => 'site_name', 'value' => 'Meem']);
        Setting::create(['group' => 'contact', 'key' => 'email', 'value' => 'hello@example.com']);

        $author = User::factory()->create();

        Post::create([
            'author_id' => $author->id,
            'post_date' => now(),
            'post_date_gmt' => now()->utc(),
            'content' => 'Published post',
            'title' => 'Published post',
            'excerpt' => '',
            'status' => 'publish',
            'comment_status' => 'open',
            'ping_status' => 'open',
            'password' => '',
            'slug' => 'published-post',
            'post_modified' => now(),
            'post_modified_gmt' => now()->utc(),
            'content_filtered' => '',
            'parent_id' => 0,
            'guid' => '',
            'menu_order' => 0,
            'type' => 'post',
            'mime_type' => '',
            'comment_count' => 0,
        ]);

        Post::create([
            'author_id' => $author->id,
            'post_date' => now(),
            'post_date_gmt' => now()->utc(),
            'content' => 'Published page',
            'title' => 'Published page',
            'excerpt' => '',
            'status' => 'publish',
            'comment_status' => 'open',
            'ping_status' => 'open',
            'password' => '',
            'slug' => 'published-page',
            'post_modified' => now(),
            'post_modified_gmt' => now()->utc(),
            'content_filtered' => '',
            'parent_id' => 0,
            'guid' => '',
            'menu_order' => 0,
            'type' => 'page',
            'mime_type' => '',
            'comment_count' => 0,
        ]);

        $postForComments = Post::create([
            'author_id' => $author->id,
            'post_date' => now(),
            'post_date_gmt' => now()->utc(),
            'content' => 'Post with comments',
            'title' => 'Post with comments',
            'excerpt' => '',
            'status' => 'publish',
            'comment_status' => 'open',
            'ping_status' => 'open',
            'password' => '',
            'slug' => 'post-with-comments',
            'post_modified' => now(),
            'post_modified_gmt' => now()->utc(),
            'content_filtered' => '',
            'parent_id' => 0,
            'guid' => '',
            'menu_order' => 0,
            'type' => 'post',
            'mime_type' => '',
            'comment_count' => 0,
        ]);

        Comment::create([
            'post_id' => $postForComments->id,
            'author_name' => 'Approved',
            'author_email' => 'approved@example.com',
            'author_url' => '',
            'author_ip' => '127.0.0.1',
            'comment_date' => now(),
            'comment_date_gmt' => now()->utc(),
            'content' => 'Approved comment',
            'karma' => 0,
            'approved' => '1',
            'agent' => 'PHPUnit',
            'type' => 'comment',
            'parent_id' => 0,
            'user_id' => 0,
        ]);

        Comment::create([
            'post_id' => $postForComments->id,
            'author_name' => 'Pending',
            'author_email' => 'pending@example.com',
            'author_url' => '',
            'author_ip' => '127.0.0.1',
            'comment_date' => now(),
            'comment_date_gmt' => now()->utc(),
            'content' => 'Pending comment',
            'karma' => 0,
            'approved' => '0',
            'agent' => 'PHPUnit',
            'type' => 'comment',
            'parent_id' => 0,
            'user_id' => 0,
        ]);

        $this->getJson('/api/v1/admin/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.business_summary.countries_count', 1)
            ->assertJsonPath('data.business_summary.branches_count', 1)
            ->assertJsonPath('data.business_summary.offer_categories_count', 1)
            ->assertJsonPath('data.business_summary.offers_count', 2)
            ->assertJsonPath('data.business_summary.active_offers_count', 1)
            ->assertJsonPath('data.business_summary.unread_contact_messages_count', 1)
            ->assertJsonPath('data.homepage_summary.is_publish_ready', true)
            ->assertJsonPath('data.homepage_summary.sliders.active', 1)
            ->assertJsonPath('data.homepage_summary.sections.active', 1)
            ->assertJsonPath('data.homepage_summary.partners.active', 1)
            ->assertJsonPath('data.homepage_summary.features.active', 1);

        $this->postJson('/api/v1/admin/dashboard/quick-draft', [])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');

        $this->postJson('/api/v1/admin/dashboard/quick-draft', [
            'title' => 'Quick Draft Title',
            'content' => 'Quick draft body',
        ])->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Quick Draft Title')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('posts', [
            'title' => 'Quick Draft Title',
            'status' => 'draft',
            'type' => 'post',
        ]);
    }

    private function authenticateWithCapabilities(array $capabilities): User
    {
        $roleSlug = 'role_' . Str::lower(Str::random(8));
        $roles = json_decode((string) Option::get('user_roles', '{}'), true);

        if (!is_array($roles)) {
            $roles = [];
        }

        $roles[$roleSlug] = [
            'name' => 'Test Role',
            'capabilities' => collect($capabilities)->mapWithKeys(fn (string $capability): array => [$capability => true])->all(),
        ];
        Option::set('user_roles', $roles, 'yes');
        OptionService::clearCache();

        $user = User::factory()->create([
            'login' => 'user_' . Str::lower(Str::random(8)),
            'nicename' => 'tester',
            'display_name' => 'Tester',
            'registered_at' => now(),
            'status' => 0,
            'url' => '',
            'activation_key' => '',
        ]);

        UserMeta::create([
            'user_id' => $user->id,
            'meta_key' => 'wp_capabilities',
            'meta_value' => json_encode([$roleSlug => true]),
        ]);

        Sanctum::actingAs($user);

        return $user;
    }
}
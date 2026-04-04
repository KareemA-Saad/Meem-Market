<?php

namespace Tests\Feature\Admin;

use App\Models\CompetitiveFeature;
use App\Models\Option;
use App\Models\Partner;
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

class HomepageManagementAdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_management_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/homepage/overview')->assertStatus(401);
        $this->getJson('/api/v1/admin/homepage/preview')->assertStatus(401);
        $this->postJson('/api/v1/admin/homepage/sections', ['title' => 'x'])->assertStatus(401);
    }

    public function test_homepage_management_routes_enforce_capability_checks(): void
    {
        $this->authenticateWithCapabilities(['read']);

        $this->getJson('/api/v1/admin/homepage/overview')->assertStatus(403);
    }

    public function test_homepage_management_crud_overview_preview_and_bulk_reorder_flow(): void
    {
        $this->authenticateWithCapabilities(['manage_options']);

        $slider = Slider::create([
            'title' => 'Hero',
            'image' => 'https://example.com/hero.webp',
            'media_type' => 'image',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Setting::create(['group' => 'general', 'key' => 'site_name', 'value' => 'Meem']);
        Setting::create(['group' => 'contact', 'key' => 'email', 'value' => 'hello@example.com']);

        $sectionCreate = $this->postJson('/api/v1/admin/homepage/sections', [
            'title' => 'Electronics',
            'is_active' => true,
            'sort_order' => 1,
        ])->assertStatus(201)->assertJsonPath('success', true);
        $sectionId = (int) $sectionCreate->json('data.id');

        $partnerCreate = $this->postJson('/api/v1/admin/homepage/partners', [
            'name' => 'Partner One',
            'logo' => 'https://example.com/logo.webp',
            'is_active' => true,
            'sort_order' => 1,
        ])->assertStatus(201)->assertJsonPath('success', true);
        $partnerId = (int) $partnerCreate->json('data.id');

        $featureCreate = $this->postJson('/api/v1/admin/homepage/features', [
            'title' => 'More Quality',
            'description' => 'Quality beyond expectations',
            'is_active' => true,
            'sort_order' => 1,
        ])->assertStatus(201)->assertJsonPath('success', true);
        $featureId = (int) $featureCreate->json('data.id');

        $this->getJson('/api/v1/admin/homepage/overview')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.sliders.active', 1)
            ->assertJsonPath('data.summary.sections.active', 1)
            ->assertJsonPath('data.summary.partners.active', 1)
            ->assertJsonPath('data.summary.features.active', 1)
            ->assertJsonPath('data.is_publish_ready', true);

        $this->getJson('/api/v1/admin/homepage/preview')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.homepage.sliders.0.id', $slider->id)
            ->assertJsonPath('data.homepage.sections.0.id', $sectionId)
            ->assertJsonPath('data.homepage.partners.0.id', $partnerId)
            ->assertJsonPath('data.homepage.features.0.id', $featureId);

        $this->putJson("/api/v1/admin/homepage/features/{$featureId}", [
            'is_active' => false,
        ])->assertOk()->assertJsonPath('data.is_active', false);

        $this->getJson('/api/v1/admin/homepage/preview')
            ->assertOk()
            ->assertJsonCount(0, 'data.homepage.features');

        $this->postJson('/api/v1/admin/homepage/features/bulk', [
            'action' => 'activate',
            'ids' => [$featureId],
        ])->assertOk()->assertJsonPath('data.affected', 1);

        $this->putJson('/api/v1/admin/homepage/sections/reorder', [
            'items' => [
                ['id' => $sectionId, 'sort_order' => 9],
            ],
        ])->assertOk()->assertJsonPath('data.affected', 1);

        $this->putJson('/api/v1/admin/homepage/partners/reorder', [
            'items' => [
                ['id' => $partnerId, 'sort_order' => 8],
            ],
        ])->assertOk()->assertJsonPath('data.affected', 1);

        $this->putJson('/api/v1/admin/homepage/features/reorder', [
            'items' => [
                ['id' => $featureId, 'sort_order' => 7],
            ],
        ])->assertOk()->assertJsonPath('data.affected', 1);

        $this->assertDatabaseHas('sections', ['id' => $sectionId, 'sort_order' => 9]);
        $this->assertDatabaseHas('partners', ['id' => $partnerId, 'sort_order' => 8]);
        $this->assertDatabaseHas('competitive_features', ['id' => $featureId, 'sort_order' => 7]);

        $this->deleteJson("/api/v1/admin/homepage/sections/{$sectionId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('sections', ['id' => $sectionId]);
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
<?php

namespace Tests\Feature\Admin;

use App\Models\Career;
use App\Models\Option;
use App\Models\User;
use App\Models\UserMeta;
use App\Services\OptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CareerAdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_career_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/careers')
            ->assertStatus(401);
    }

    public function test_career_routes_enforce_capability_checks(): void
    {
        $this->authenticateWithCapabilities(['read']);

        $this->getJson('/api/v1/admin/careers')
            ->assertStatus(403);
    }

    public function test_career_crud_flow_with_duplicate_slug_validation(): void
    {
        $this->authenticateWithCapabilities(['manage_options']);

        $create = $this->postJson('/api/v1/admin/careers', [
            'title' => 'Store Cashier',
            'slug' => 'store-cashier',
            'location' => 'Riyadh',
            'type' => 'Full Time',
            'description' => 'Career description',
            'requirements' => 'Requirements',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $create->assertStatus(201)->assertJsonPath('success', true);
        $careerId = (int) $create->json('data.id');

        $this->postJson('/api/v1/admin/careers', [
            'title' => 'Another Role',
            'slug' => 'store-cashier',
            'description' => 'Duplicate slug',
        ])->assertStatus(422)->assertJsonPath('code', 'DUPLICATE_SLUG');

        $this->getJson("/api/v1/admin/careers/{$careerId}")
            ->assertOk()
            ->assertJsonPath('data.id', $careerId);

        $this->putJson("/api/v1/admin/careers/{$careerId}", [
            'title' => 'Store Cashier Updated',
            'is_active' => false,
            'sort_order' => 5,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('careers', [
            'id' => $careerId,
            'title' => 'Store Cashier Updated',
            'is_active' => false,
            'sort_order' => 5,
        ]);

        $this->deleteJson("/api/v1/admin/careers/{$careerId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('careers', ['id' => $careerId]);
    }

    public function test_career_list_bulk_reorder_and_validation_paths(): void
    {
        $this->authenticateWithCapabilities(['manage_options']);

        $first = Career::create([
            'title' => 'Role A',
            'slug' => 'role-a',
            'description' => 'A',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $second = Career::create([
            'title' => 'Role B',
            'slug' => 'role-b',
            'description' => 'B',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->getJson('/api/v1/admin/careers?sort_by=sort_order&sort_dir=desc')
            ->assertOk()
            ->assertJsonPath('data.0.id', $second->id);

        $this->postJson('/api/v1/admin/careers/bulk', [
            'action' => 'deactivate',
            'ids' => [$first->id, $second->id],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('careers', ['id' => $first->id, 'is_active' => false]);
        $this->assertDatabaseHas('careers', ['id' => $second->id, 'is_active' => false]);

        $this->putJson('/api/v1/admin/careers/reorder', [
            'items' => [
                ['id' => $first->id, 'sort_order' => 20],
                ['id' => $second->id, 'sort_order' => 3],
            ],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('careers', ['id' => $first->id, 'sort_order' => 20]);
        $this->assertDatabaseHas('careers', ['id' => $second->id, 'sort_order' => 3]);

        $this->postJson('/api/v1/admin/careers/bulk', [
            'action' => 'unsupported',
            'ids' => [$first->id],
        ])->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR');
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

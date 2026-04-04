<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Country;
use App\Models\Option;
use App\Models\User;
use App\Models\UserMeta;
use App\Services\OptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BranchAdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/branches')
            ->assertStatus(401);
    }

    public function test_branch_routes_enforce_capability_checks(): void
    {
        $this->authenticateWithCapabilities(['read']);

        $this->getJson('/api/v1/admin/branches')
            ->assertStatus(403);
    }

    public function test_branch_crud_flow_with_duplicate_slug_validation(): void
    {
        $this->authenticateWithCapabilities(['manage_options']);
        $country = $this->createCountry('saudi-arabia');
        $otherCountry = $this->createCountry('kuwait');

        $createResponse = $this->postJson('/api/v1/admin/branches', [
            'country_id' => $country->id,
            'name_ar' => 'فرع الأحساء',
            'name_en' => 'Al Ahsa',
            'slug' => 'ahsa',
            'address' => 'Al Ahsa',
            'google_maps_url' => 'https://maps.app.goo.gl/example',
            'latitude' => 25.4017469,
            'longitude' => 49.5600663,
            'phone' => '0551297970',
            'unified_phone' => '920010937',
            'social_links' => ['instagram' => 'https://instagram.com/example'],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $createResponse->assertStatus(201)->assertJsonPath('success', true);
        $branchId = (int) $createResponse->json('data.id');

        $this->postJson('/api/v1/admin/branches', [
            'country_id' => $country->id,
            'name_ar' => 'فرع ثاني',
            'slug' => 'ahsa',
        ])->assertStatus(422)->assertJsonPath('code', 'DUPLICATE_SLUG');

        $this->getJson("/api/v1/admin/branches/{$branchId}")
            ->assertOk()
            ->assertJsonPath('data.id', $branchId)
            ->assertJsonPath('data.country_id', $country->id);

        $this->putJson("/api/v1/admin/branches/{$branchId}", [
            'country_id' => $otherCountry->id,
            'name_en' => 'Al Ahsa Updated',
            'sort_order' => 8,
            'is_active' => false,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('branches', [
            'id' => $branchId,
            'country_id' => $otherCountry->id,
            'name_en' => 'Al Ahsa Updated',
            'sort_order' => 8,
            'is_active' => false,
        ]);

        $this->deleteJson("/api/v1/admin/branches/{$branchId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('branches', ['id' => $branchId]);
    }

    public function test_branch_list_bulk_reorder_and_validation_paths(): void
    {
        $this->authenticateWithCapabilities(['manage_options']);
        $country = $this->createCountry('saudi-arabia');
        $otherCountry = $this->createCountry('kuwait');

        $first = Branch::create([
            'country_id' => $country->id,
            'name_ar' => 'فرع 1',
            'name_en' => 'Branch 1',
            'slug' => 'branch-1',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $second = Branch::create([
            'country_id' => $country->id,
            'name_ar' => 'فرع 2',
            'name_en' => 'Branch 2',
            'slug' => 'branch-2',
            'is_active' => true,
            'sort_order' => 10,
        ]);
        Branch::create([
            'country_id' => $otherCountry->id,
            'name_ar' => 'فرع 3',
            'name_en' => 'Branch 3',
            'slug' => 'branch-3',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        $this->getJson("/api/v1/admin/branches?country_id={$country->id}&sort_by=sort_order&sort_dir=desc")
            ->assertOk()
            ->assertJsonPath('data.0.id', $second->id);

        $this->postJson('/api/v1/admin/branches/bulk', [
            'action' => 'deactivate',
            'ids' => [$first->id, $second->id],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('branches', ['id' => $first->id, 'is_active' => false]);
        $this->assertDatabaseHas('branches', ['id' => $second->id, 'is_active' => false]);

        $this->putJson('/api/v1/admin/branches/reorder', [
            'items' => [
                ['id' => $first->id, 'sort_order' => 20],
                ['id' => $second->id, 'sort_order' => 3],
            ],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('branches', ['id' => $first->id, 'sort_order' => 20]);
        $this->assertDatabaseHas('branches', ['id' => $second->id, 'sort_order' => 3]);

        $this->postJson('/api/v1/admin/branches/bulk', [
            'action' => 'unsupported',
            'ids' => [$first->id],
        ])->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    private function createCountry(string $slug): Country
    {
        return Country::create([
            'name_ar' => 'دولة',
            'name_en' => Str::title(str_replace('-', ' ', $slug)),
            'slug' => $slug . '-' . Str::lower(Str::random(4)),
            'is_active' => true,
            'sort_order' => 1,
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

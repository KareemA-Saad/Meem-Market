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

class CountryAdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/countries')
            ->assertStatus(401);
    }

    public function test_country_routes_enforce_capability_checks(): void
    {
        $this->authenticateWithCapabilities(['read']);

        $this->getJson('/api/v1/admin/countries')
            ->assertStatus(403);
    }

    public function test_country_crud_flow_and_cascade_delete(): void
    {
        $this->authenticateWithCapabilities(['manage_options']);

        $createResponse = $this->postJson('/api/v1/admin/countries', [
            'name_ar' => 'السعودية',
            'name_en' => 'Saudi Arabia',
            'slug' => 'saudi-arabia',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $createResponse->assertStatus(201)->assertJsonPath('success', true);
        $countryId = (int) $createResponse->json('data.id');

        Branch::create([
            'country_id' => $countryId,
            'name_ar' => 'فرع الرياض',
            'name_en' => 'Riyadh Branch',
            'slug' => 'riyadh-branch',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->getJson("/api/v1/admin/countries/{$countryId}")
            ->assertOk()
            ->assertJsonPath('data.id', $countryId)
            ->assertJsonPath('data.branches.0.country_id', $countryId);

        $this->putJson("/api/v1/admin/countries/{$countryId}", [
            'name_en' => 'Saudi Arabia Updated',
            'sort_order' => 9,
            'is_active' => false,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('countries', [
            'id' => $countryId,
            'name_en' => 'Saudi Arabia Updated',
            'sort_order' => 9,
            'is_active' => false,
        ]);

        $this->deleteJson("/api/v1/admin/countries/{$countryId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('countries', ['id' => $countryId]);
        $this->assertDatabaseMissing('branches', ['country_id' => $countryId]);
    }

    public function test_country_list_bulk_reorder_and_validation_paths(): void
    {
        $this->authenticateWithCapabilities(['manage_options']);

        $first = Country::create([
            'name_ar' => 'الكويت',
            'name_en' => 'Kuwait',
            'slug' => 'kuwait',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $second = Country::create([
            'name_ar' => 'السعودية',
            'name_en' => 'Saudi Arabia',
            'slug' => 'saudi-arabia',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->getJson('/api/v1/admin/countries?sort_by=sort_order&sort_dir=desc')
            ->assertOk()
            ->assertJsonPath('data.0.id', $second->id);

        $this->postJson('/api/v1/admin/countries/bulk', [
            'action' => 'deactivate',
            'ids' => [$first->id, $second->id],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('countries', ['id' => $first->id, 'is_active' => false]);
        $this->assertDatabaseHas('countries', ['id' => $second->id, 'is_active' => false]);

        $this->putJson('/api/v1/admin/countries/reorder', [
            'items' => [
                ['id' => $first->id, 'sort_order' => 20],
                ['id' => $second->id, 'sort_order' => 5],
            ],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('countries', ['id' => $first->id, 'sort_order' => 20]);
        $this->assertDatabaseHas('countries', ['id' => $second->id, 'sort_order' => 5]);

        $this->postJson('/api/v1/admin/countries/bulk', [
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

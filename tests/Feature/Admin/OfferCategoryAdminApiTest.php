<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Country;
use App\Models\OfferCategory;
use App\Models\Option;
use App\Services\OptionService;
use App\Models\User;
use App\Models\UserMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OfferCategoryAdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_offer_category_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/offer-categories')
            ->assertStatus(401);
    }

    public function test_offer_category_routes_enforce_capability_checks(): void
    {
        $this->authenticateWithCapabilities(['read']);

        $this->getJson('/api/v1/admin/offer-categories')
            ->assertStatus(403);
    }

    public function test_offer_category_crud_flow_with_cover_upload(): void
    {
        Storage::fake('public');
        $this->authenticateWithCapabilities(['manage_offer_categories']);
        $branch = $this->createBranch();

        $createResponse = $this->post('/api/v1/admin/offer-categories', [
            'branch_id' => $branch->id,
            'title' => 'Summer 2026',
            'cover_image' => UploadedFile::fake()->create('cover.jpg', 120, 'image/jpeg'),
            'start_date' => '2026-06-01',
            'end_date' => '2026-08-31',
            'is_active' => true,
            'sort_order' => 1,
        ], ['Accept' => 'application/json']);

        $createResponse->assertStatus(201)->assertJsonPath('success', true);
        $categoryId = (int) $createResponse->json('data.id');

        $this->assertDatabaseHas('offer_categories', [
            'id' => $categoryId,
            'title' => 'Summer 2026',
            'branch_id' => $branch->id,
        ]);

        $this->getJson("/api/v1/admin/offer-categories/{$categoryId}")
            ->assertOk()
            ->assertJsonPath('data.id', $categoryId);

        $this->putJson("/api/v1/admin/offer-categories/{$categoryId}", [
            'title' => 'Summer 2026 Updated',
            'slug' => 'summer-updated',
            'sort_order' => 8,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('offer_categories', [
            'id' => $categoryId,
            'title' => 'Summer 2026 Updated',
            'slug' => 'summer-updated',
            'sort_order' => 8,
        ]);

        $this->deleteJson("/api/v1/admin/offer-categories/{$categoryId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('offer_categories', ['id' => $categoryId]);
    }

    public function test_offer_category_update_rejects_duplicate_slug_for_branch(): void
    {
        $this->authenticateWithCapabilities(['manage_offer_categories']);
        $branch = $this->createBranch();

        $first = OfferCategory::create([
            'branch_id' => $branch->id,
            'title' => 'First',
            'slug' => 'duplicate-slug',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $second = OfferCategory::create([
            'branch_id' => $branch->id,
            'title' => 'Second',
            'slug' => 'second-slug',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->putJson("/api/v1/admin/offer-categories/{$second->id}", [
            'slug' => $first->slug,
        ])->assertStatus(422)
            ->assertJsonPath('code', 'DUPLICATE_SLUG');
    }

    public function test_offer_category_list_bulk_reorder_and_validation_paths(): void
    {
        $this->authenticateWithCapabilities(['manage_offer_categories']);
        $branchA = $this->createBranch('saudi-main');
        $branchB = $this->createBranch('saudi-sub');

        $a1 = OfferCategory::create([
            'branch_id' => $branchA->id,
            'title' => 'Alpha',
            'slug' => 'alpha',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $a2 = OfferCategory::create([
            'branch_id' => $branchA->id,
            'title' => 'Beta',
            'slug' => 'beta',
            'is_active' => true,
            'sort_order' => 9,
        ]);
        OfferCategory::create([
            'branch_id' => $branchB->id,
            'title' => 'Gamma',
            'slug' => 'gamma',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $listResponse = $this->getJson("/api/v1/admin/offer-categories?branch_id={$branchA->id}&sort_by=sort_order&sort_dir=desc");
        $listResponse->assertOk()->assertJsonPath('success', true);
        $items = $this->extractItems($listResponse->json());
        $this->assertCount(2, $items);
        $this->assertSame($a2->id, $items[0]['id']);

        $this->postJson('/api/v1/admin/offer-categories/bulk', [
            'action' => 'deactivate',
            'ids' => [$a1->id, $a2->id],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('offer_categories', ['id' => $a1->id, 'is_active' => false]);
        $this->assertDatabaseHas('offer_categories', ['id' => $a2->id, 'is_active' => false]);

        $this->putJson('/api/v1/admin/offer-categories/reorder', [
            'items' => [
                ['id' => $a1->id, 'sort_order' => 20],
                ['id' => $a2->id, 'sort_order' => 10],
            ],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('offer_categories', ['id' => $a1->id, 'sort_order' => 20]);
        $this->assertDatabaseHas('offer_categories', ['id' => $a2->id, 'sort_order' => 10]);

        $this->postJson('/api/v1/admin/offer-categories/bulk', [
            'action' => 'unsupported',
            'ids' => [$a1->id],
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

    private function createBranch(string $slug = 'saudi-branch'): Branch
    {
        $country = Country::create([
            'name_ar' => 'Saudi Arabia',
            'name_en' => 'Saudi Arabia',
            'slug' => 'saudia-' . Str::lower(Str::random(5)),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return Branch::create([
            'country_id' => $country->id,
            'name_ar' => 'Branch',
            'name_en' => 'Branch',
            'slug' => $slug . '-' . Str::lower(Str::random(4)),
            'address' => 'Address',
            'google_maps_url' => null,
            'latitude' => null,
            'longitude' => null,
            'phone' => null,
            'unified_phone' => null,
            'social_links' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function extractItems(array $payload): array
    {
        if (isset($payload['data']['data']) && is_array($payload['data']['data'])) {
            return $payload['data']['data'];
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            return $payload['data'];
        }

        return [];
    }
}






<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Country;
use App\Models\Offer;
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

class OfferAdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_offer_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/offers')
            ->assertStatus(401);
    }

    public function test_offer_routes_return_json_401_for_plain_api_requests(): void
    {
        $this->get('/api/v1/admin/offers')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_offer_routes_enforce_capability_checks(): void
    {
        $this->authenticateWithCapabilities(['read']);

        $this->getJson('/api/v1/admin/offers')
            ->assertStatus(403);
    }

    public function test_offer_crud_flow_with_file_upload_and_validation_error(): void
    {
        Storage::fake('public');
        $this->authenticateWithCapabilities(['manage_offers']);
        $category = $this->createOfferCategory();

        $this->postJson('/api/v1/admin/offers', [
            'offer_category_id' => $category->id,
            'title' => 'Invalid Without Image',
        ])->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR');

        $createResponse = $this->post('/api/v1/admin/offers', [
            'offer_category_id' => $category->id,
            'title' => 'Offer One',
            'image' => UploadedFile::fake()->create('offer.jpg', 90, 'image/jpeg'),
            'is_active' => true,
            'sort_order' => 3,
        ], ['Accept' => 'application/json']);

        $createResponse->assertStatus(201)->assertJsonPath('success', true);
        $offerId = (int) $createResponse->json('data.id');

        $this->assertDatabaseHas('offers', [
            'id' => $offerId,
            'offer_category_id' => $category->id,
            'title' => 'Offer One',
            'sort_order' => 3,
        ]);

        $this->getJson("/api/v1/admin/offers/{$offerId}")
            ->assertOk()
            ->assertJsonPath('data.id', $offerId);

        $this->putJson("/api/v1/admin/offers/{$offerId}", [
            'title' => 'Offer One Updated',
            'sort_order' => 12,
            'is_active' => false,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('offers', [
            'id' => $offerId,
            'title' => 'Offer One Updated',
            'sort_order' => 12,
            'is_active' => false,
        ]);

        $this->deleteJson("/api/v1/admin/offers/{$offerId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('offers', ['id' => $offerId]);
    }

    public function test_offer_list_filters_bulk_and_reorder_paths(): void
    {
        $this->authenticateWithCapabilities(['manage_offers']);
        $categoryA = $this->createOfferCategory('cat-a');
        $categoryB = $this->createOfferCategory('cat-b');

        $offerA = Offer::create([
            'offer_category_id' => $categoryA->id,
            'title' => 'Alpha',
            'image' => 'https://example.com/a.jpg',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $offerB = Offer::create([
            'offer_category_id' => $categoryA->id,
            'title' => 'Beta',
            'image' => 'https://example.com/b.jpg',
            'is_active' => true,
            'sort_order' => 9,
        ]);
        Offer::create([
            'offer_category_id' => $categoryB->id,
            'title' => 'Gamma',
            'image' => 'https://example.com/c.jpg',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        $listResponse = $this->getJson("/api/v1/admin/offers?offer_category_id={$categoryA->id}&sort_by=sort_order&sort_dir=desc");
        $listResponse->assertOk()->assertJsonPath('success', true);
        $items = $this->extractItems($listResponse->json());
        $this->assertCount(2, $items);
        $this->assertSame($offerB->id, $items[0]['id']);

        $this->postJson('/api/v1/admin/offers/bulk', [
            'action' => 'deactivate',
            'ids' => [$offerA->id, $offerB->id],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('offers', ['id' => $offerA->id, 'is_active' => false]);
        $this->assertDatabaseHas('offers', ['id' => $offerB->id, 'is_active' => false]);

        $this->putJson('/api/v1/admin/offers/reorder', [
            'items' => [
                ['id' => $offerA->id, 'sort_order' => 50],
                ['id' => $offerB->id, 'sort_order' => 10],
            ],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('offers', ['id' => $offerA->id, 'sort_order' => 50]);
        $this->assertDatabaseHas('offers', ['id' => $offerB->id, 'sort_order' => 10]);

        $this->postJson('/api/v1/admin/offers/bulk', [
            'action' => 'unsupported',
            'ids' => [$offerA->id],
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

    private function createOfferCategory(string $slugPrefix = 'offer-cat'): OfferCategory
    {
        $country = Country::create([
            'name_ar' => 'Saudi Arabia',
            'name_en' => 'Saudi Arabia',
            'slug' => 'country-' . Str::lower(Str::random(5)),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $branch = Branch::create([
            'country_id' => $country->id,
            'name_ar' => 'Branch',
            'name_en' => 'Branch',
            'slug' => 'branch-' . Str::lower(Str::random(5)),
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

        return OfferCategory::create([
            'branch_id' => $branch->id,
            'title' => 'Offers ' . Str::upper(Str::random(3)),
            'slug' => $slugPrefix . '-' . Str::lower(Str::random(4)),
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






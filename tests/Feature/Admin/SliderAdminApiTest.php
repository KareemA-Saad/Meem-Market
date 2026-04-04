<?php

namespace Tests\Feature\Admin;

use App\Models\Option;
use App\Models\Slider;
use App\Models\User;
use App\Models\UserMeta;
use App\Services\OptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SliderAdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_slider_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/sliders')
            ->assertStatus(401);
    }

    public function test_slider_routes_enforce_capability_checks(): void
    {
        $this->authenticateWithCapabilities(['read']);

        $this->getJson('/api/v1/admin/sliders')
            ->assertStatus(403);
    }

    public function test_slider_crud_flow_with_file_upload_and_validation_error(): void
    {
        Storage::fake('public');
        $this->authenticateWithCapabilities(['manage_options']);

        $this->postJson('/api/v1/admin/sliders', [
            'title' => 'Missing Image',
        ])->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR');

        $createResponse = $this->post('/api/v1/admin/sliders', [
            'title' => 'Slider One',
            'image' => UploadedFile::fake()->create('slider.jpg', 120, 'image/jpeg'),
            'media_type' => 'video',
            'link' => 'https://meem.market/offers',
            'is_active' => true,
            'sort_order' => 1,
        ], ['Accept' => 'application/json']);

        $createResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.media_type', 'video');
        $sliderId = (int) $createResponse->json('data.id');

        $this->assertDatabaseHas('sliders', [
            'id' => $sliderId,
            'title' => 'Slider One',
            'media_type' => 'video',
            'link' => 'https://meem.market/offers',
            'sort_order' => 1,
        ]);

        $this->getJson("/api/v1/admin/sliders/{$sliderId}")
            ->assertOk()
            ->assertJsonPath('data.id', $sliderId);

        $this->putJson("/api/v1/admin/sliders/{$sliderId}", [
            'title' => 'Slider One Updated',
            'media_type' => 'image',
            'sort_order' => 9,
            'is_active' => false,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.media_type', 'image');

        $this->assertDatabaseHas('sliders', [
            'id' => $sliderId,
            'title' => 'Slider One Updated',
            'media_type' => 'image',
            'sort_order' => 9,
            'is_active' => false,
        ]);

        $this->deleteJson("/api/v1/admin/sliders/{$sliderId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('sliders', ['id' => $sliderId]);
    }

    public function test_slider_list_bulk_and_reorder_paths(): void
    {
        $this->authenticateWithCapabilities(['manage_options']);

        $first = Slider::create([
            'title' => 'Slider A',
            'image' => 'https://example.com/a.jpg',
            'media_type' => 'image',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $second = Slider::create([
            'title' => 'Slider B',
            'image' => 'https://example.com/b.jpg',
            'media_type' => 'video',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->getJson('/api/v1/admin/sliders?sort_by=sort_order&sort_dir=desc')
            ->assertOk()
            ->assertJsonPath('data.0.id', $second->id);

        $this->getJson('/api/v1/admin/sliders?media_type=video')
            ->assertOk()
            ->assertJsonPath('data.0.id', $second->id);

        $this->postJson('/api/v1/admin/sliders/bulk', [
            'action' => 'deactivate',
            'ids' => [$first->id, $second->id],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('sliders', ['id' => $first->id, 'is_active' => false]);
        $this->assertDatabaseHas('sliders', ['id' => $second->id, 'is_active' => false]);

        $this->putJson('/api/v1/admin/sliders/reorder', [
            'items' => [
                ['id' => $first->id, 'sort_order' => 20],
                ['id' => $second->id, 'sort_order' => 3],
            ],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('sliders', ['id' => $first->id, 'sort_order' => 20]);
        $this->assertDatabaseHas('sliders', ['id' => $second->id, 'sort_order' => 3]);

        $this->postJson('/api/v1/admin/sliders/bulk', [
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

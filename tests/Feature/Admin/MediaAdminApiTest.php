<?php

namespace Tests\Feature\Admin;

use App\Models\Option;
use App\Models\User;
use App\Models\UserMeta;
use App\Services\OptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MediaAdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/media')
            ->assertStatus(401);
    }

    public function test_media_routes_enforce_capability_checks(): void
    {
        $this->authenticateWithCapabilities(['read']);

        $this->getJson('/api/v1/admin/media')
            ->assertStatus(403);
    }

    public function test_media_upload_list_update_edit_and_bulk_delete_flow(): void
    {
        Storage::fake('public');
        $this->authenticateWithCapabilities(['upload_files']);

        $uploadResponse = $this->post('/api/v1/admin/media/upload', [
            'files' => [
                UploadedFile::fake()->create('brochure.pdf', 128, 'application/pdf'),
            ],
        ], ['Accept' => 'application/json']);

        $uploadResponse->assertStatus(201)->assertJsonPath('success', true);
        $mediaId = (int) $uploadResponse->json('data.0.id');

        $this->assertDatabaseHas('posts', [
            'id' => $mediaId,
            'type' => 'attachment',
        ]);

        $this->getJson('/api/v1/admin/media?type=document&search=brochure')
            ->assertOk()
            ->assertJsonPath('data.0.id', $mediaId);

        $this->getJson("/api/v1/admin/media/{$mediaId}")
            ->assertOk()
            ->assertJsonPath('data.id', $mediaId);

        $this->putJson("/api/v1/admin/media/{$mediaId}", [
            'title' => 'Brochure 2026',
            'caption' => 'Campaign Brochure',
            'alt_text' => 'Brochure Alt',
            'description' => 'Brochure Description',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Brochure 2026')
            ->assertJsonPath('data.alt_text', 'Brochure Alt');

        $this->assertDatabaseHas('posts', [
            'id' => $mediaId,
            'title' => 'Brochure 2026',
            'excerpt' => 'Campaign Brochure',
            'content' => 'Brochure Description',
        ]);

        $this->assertDatabaseHas('post_meta', [
            'post_id' => $mediaId,
            'meta_key' => '_wp_attachment_image_alt',
            'meta_value' => 'Brochure Alt',
        ]);

        $this->postJson("/api/v1/admin/media/{$mediaId}/edit", [
            'action' => 'scale',
            'params' => ['width' => 50],
        ])->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->postJson('/api/v1/admin/media/bulk', [
            'action' => 'delete',
            'media_ids' => [$mediaId],
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.affected', 1);

        $this->assertDatabaseMissing('posts', ['id' => $mediaId]);
    }

    public function test_media_validation_returns_admin_error_shape(): void
    {
        $this->authenticateWithCapabilities(['upload_files']);

        $this->postJson('/api/v1/admin/media/upload', [])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');

        $this->postJson('/api/v1/admin/media/bulk', [
            'action' => 'trash',
            'media_ids' => [],
        ])->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
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

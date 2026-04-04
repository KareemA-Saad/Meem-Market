<?php

namespace Tests\Feature\Admin;

use App\Models\Option;
use App\Models\User;
use App\Models\UserMeta;
use App\Services\OptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SettingsAdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/settings/general')
            ->assertStatus(401);
    }

    public function test_settings_routes_enforce_capability_checks(): void
    {
        $this->authenticateWithCapabilities(['read']);

        $this->getJson('/api/v1/admin/settings/general')
            ->assertStatus(403);
    }

    public function test_settings_show_update_and_validation_paths(): void
    {
        $this->authenticateWithCapabilities(['manage_options']);

        $this->getJson('/api/v1/admin/settings/general')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.blogname', 'MeemMark')
            ->assertJsonPath('data.users_can_register', false);

        $this->putJson('/api/v1/admin/settings/general', [
            'blogname' => 'Meem Market Admin',
            'admin_email' => 'admin@example.com',
            'users_can_register' => true,
            'start_of_week' => 6,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.blogname', 'Meem Market Admin')
            ->assertJsonPath('data.admin_email', 'admin@example.com')
            ->assertJsonPath('data.users_can_register', true)
            ->assertJsonPath('data.start_of_week', 6);

        $this->assertDatabaseHas('options', [
            'name' => 'blogname',
            'value' => 'Meem Market Admin',
        ]);
        $this->assertDatabaseHas('options', [
            'name' => 'admin_email',
            'value' => 'admin@example.com',
        ]);
        $this->assertDatabaseHas('options', [
            'name' => 'users_can_register',
            'value' => '1',
        ]);
        $this->assertDatabaseHas('options', [
            'name' => 'start_of_week',
            'value' => '6',
        ]);

        $this->putJson('/api/v1/admin/settings/general', [
            'admin_email' => 'not-an-email',
        ])->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');

        $this->putJson('/api/v1/admin/settings/general', [
            'unknown_key' => 'value',
        ])->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->getJson('/api/v1/admin/settings/unknown')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
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

<?php

namespace Tests\Feature\Admin;

use App\Mail\NewUserRegistrationMail;
use App\Models\Option;
use App\Models\Post;
use App\Models\User;
use App\Models\UserMeta;
use App\Services\OptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserAdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/users')
            ->assertStatus(401);
    }

    public function test_user_routes_enforce_capability_checks(): void
    {
        $this->authenticateWithCapabilities(['read']);

        $this->getJson('/api/v1/admin/users')
            ->assertStatus(403);
    }

    public function test_user_crud_flow_with_role_validation_and_reassignment(): void
    {
        Mail::fake();
        $admin = $this->authenticateWithCapabilities(['list_users', 'create_users', 'edit_users', 'delete_users', 'promote_users']);
        $this->upsertRoles([
            'editor' => ['name' => 'Editor', 'capabilities' => ['read' => true]],
            'subscriber' => ['name' => 'Subscriber', 'capabilities' => ['read' => true]],
        ]);

        $this->postJson('/api/v1/admin/users', [
            'login' => 'invalid-role-user',
            'email' => 'invalid-role@example.com',
            'role' => 'unknown-role',
        ])->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR');

        $createResponse = $this->postJson('/api/v1/admin/users', [
            'login' => 'new-editor',
            'email' => 'new-editor@example.com',
            'role' => 'editor',
            'first_name' => 'New',
            'last_name' => 'Editor',
            'send_notification' => true,
        ]);

        $createResponse->assertStatus(201)->assertJsonPath('success', true);
        $userId = (int) $createResponse->json('data.id');

        Mail::assertSent(NewUserRegistrationMail::class);
        $this->assertDatabaseHas('users', ['id' => $userId, 'email' => 'new-editor@example.com']);

        $this->getJson("/api/v1/admin/users/{$userId}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $userId);

        $this->putJson("/api/v1/admin/users/{$userId}", [
            'display_name' => 'Updated Editor',
            'role' => 'subscriber',
            'bio' => 'Updated bio',
        ])->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.role', 'subscriber');

        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'display_name' => 'Updated Editor',
        ]);
        $this->assertDatabaseHas('user_meta', [
            'user_id' => $userId,
            'meta_key' => 'description',
            'meta_value' => 'Updated bio',
        ]);

        Post::create([
            'author_id' => $userId,
            'slug' => 'post-' . Str::lower(Str::random(8)),
            'title' => 'User Post',
        ]);

        $this->deleteJson("/api/v1/admin/users/{$userId}?reassign_to={$admin->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('users', ['id' => $userId]);
        $this->assertDatabaseHas('posts', ['author_id' => $admin->id]);
    }

    public function test_bulk_change_role_requires_promote_users_capability(): void
    {
        $this->authenticateWithCapabilities(['delete_users']);
        $this->upsertRoles([
            'editor' => ['name' => 'Editor', 'capabilities' => ['read' => true]],
            'subscriber' => ['name' => 'Subscriber', 'capabilities' => ['read' => true]],
        ]);

        $first = $this->createUserWithRole('editor');
        $second = $this->createUserWithRole('editor');

        $this->postJson('/api/v1/admin/users/bulk', [
            'action' => 'change_role',
            'user_ids' => [$first->id, $second->id],
            'role' => 'subscriber',
        ])->assertStatus(403)->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_bulk_delete_rejects_reassign_target_inside_delete_list(): void
    {
        $this->authenticateWithCapabilities(['delete_users', 'promote_users']);
        $this->upsertRoles([
            'editor' => ['name' => 'Editor', 'capabilities' => ['read' => true]],
        ]);

        $first = $this->createUserWithRole('editor');
        $second = $this->createUserWithRole('editor');

        $this->postJson('/api/v1/admin/users/bulk', [
            'action' => 'delete',
            'user_ids' => [$first->id, $second->id],
            'reassign_to' => $second->id,
        ])->assertStatus(422)->assertJsonPath('code', 'INVALID_REASSIGN_TARGET');
    }

    private function authenticateWithCapabilities(array $capabilities): User
    {
        $roleSlug = 'role_' . Str::lower(Str::random(8));
        $this->upsertRoles([
            $roleSlug => [
                'name' => 'Test Role',
                'capabilities' => collect($capabilities)->mapWithKeys(fn (string $capability): array => [$capability => true])->all(),
            ],
        ]);

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

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create([
            'login' => 'user_' . Str::lower(Str::random(8)),
            'nicename' => 'member',
            'display_name' => 'Member',
            'registered_at' => now(),
            'status' => 0,
            'url' => '',
            'activation_key' => '',
        ]);

        UserMeta::create([
            'user_id' => $user->id,
            'meta_key' => 'wp_capabilities',
            'meta_value' => json_encode([$role => true]),
        ]);

        return $user;
    }

    /**
     * @param array<string, array{name: string, capabilities: array<string, bool>>> $rolesToMerge
     */
    private function upsertRoles(array $rolesToMerge): void
    {
        $existing = json_decode((string) Option::get('user_roles', '{}'), true);

        if (!is_array($existing)) {
            $existing = [];
        }

        foreach ($rolesToMerge as $slug => $definition) {
            $existing[$slug] = $definition;
        }

        Option::set('user_roles', $existing, 'yes');
        OptionService::clearCache();
    }
}

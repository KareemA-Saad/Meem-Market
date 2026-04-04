<?php

namespace Tests\Feature\Admin;

use App\Models\ContactMessage;
use App\Models\Option;
use App\Models\User;
use App\Models\UserMeta;
use App\Services\OptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactMessageAdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_message_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/contact-messages')
            ->assertStatus(401);
    }

    public function test_contact_message_routes_enforce_capability_checks(): void
    {
        $this->authenticateWithCapabilities(['read']);

        $this->getJson('/api/v1/admin/contact-messages')
            ->assertStatus(403);
    }

    public function test_contact_message_listing_show_update_and_delete_flow(): void
    {
        $this->authenticateWithCapabilities(['manage_options']);

        $message = ContactMessage::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '0500000000',
            'subject' => 'Inquiry',
            'message' => 'Need more details',
        ]);

        $this->getJson('/api/v1/admin/contact-messages?is_read=false')
            ->assertOk()
            ->assertJsonPath('data.0.id', $message->id);

        $this->getJson("/api/v1/admin/contact-messages/{$message->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $message->id)
            ->assertJsonPath('data.is_read', false);

        $this->putJson("/api/v1/admin/contact-messages/{$message->id}", [
            'is_read' => true,
        ])->assertOk()->assertJsonPath('data.is_read', true);

        $this->assertDatabaseHas('contact_messages', [
            'id' => $message->id,
            'is_read' => true,
        ]);

        $this->deleteJson("/api/v1/admin/contact-messages/{$message->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('contact_messages', ['id' => $message->id]);
    }

    public function test_contact_message_bulk_actions_and_validation_path(): void
    {
        $this->authenticateWithCapabilities(['manage_options']);

        $first = ContactMessage::create([
            'name' => 'First',
            'email' => 'first@example.com',
            'message' => 'First message',
        ]);
        $second = ContactMessage::create([
            'name' => 'Second',
            'email' => 'second@example.com',
            'message' => 'Second message',
        ]);

        $this->postJson('/api/v1/admin/contact-messages/bulk', [
            'action' => 'mark_read',
            'ids' => [$first->id, $second->id],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('contact_messages', ['id' => $first->id, 'is_read' => true]);
        $this->assertDatabaseHas('contact_messages', ['id' => $second->id, 'is_read' => true]);

        $this->postJson('/api/v1/admin/contact-messages/bulk', [
            'action' => 'mark_unread',
            'ids' => [$first->id],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('contact_messages', ['id' => $first->id, 'is_read' => false]);

        $this->postJson('/api/v1/admin/contact-messages/bulk', [
            'action' => 'delete',
            'ids' => [$first->id, $second->id],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseMissing('contact_messages', ['id' => $first->id]);
        $this->assertDatabaseMissing('contact_messages', ['id' => $second->id]);

        $this->postJson('/api/v1/admin/contact-messages/bulk', [
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

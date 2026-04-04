<?php

namespace Tests\Feature\Admin;

use App\Models\Comment;
use App\Models\Option;
use App\Models\Post;
use App\Models\User;
use App\Models\UserMeta;
use App\Services\OptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentAdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/comments')
            ->assertStatus(401);
    }

    public function test_comment_routes_enforce_capability_checks(): void
    {
        $this->authenticateWithCapabilities(['read']);

        $this->getJson('/api/v1/admin/comments')
            ->assertStatus(403);
    }

    public function test_comment_moderation_flow(): void
    {
        $this->authenticateWithCapabilities(['moderate_comments']);

        $post = Post::factory()->create([
            'type' => 'post',
            'status' => 'publish',
        ]);

        $comment = Comment::create([
            'post_id' => $post->id,
            'author_name' => 'Pending User',
            'author_email' => 'pending@example.com',
            'author_url' => '',
            'author_ip' => '127.0.0.1',
            'comment_date' => now(),
            'comment_date_gmt' => now()->utc(),
            'content' => 'Pending comment',
            'karma' => 0,
            'approved' => '0',
            'agent' => 'PHPUnit',
            'type' => 'comment',
            'parent_id' => 0,
            'user_id' => 0,
        ]);

        $bulkTarget = Comment::create([
            'post_id' => $post->id,
            'author_name' => 'Second User',
            'author_email' => 'second@example.com',
            'author_url' => '',
            'author_ip' => '127.0.0.1',
            'comment_date' => now(),
            'comment_date_gmt' => now()->utc(),
            'content' => 'Second comment',
            'karma' => 0,
            'approved' => '1',
            'agent' => 'PHPUnit',
            'type' => 'comment',
            'parent_id' => 0,
            'user_id' => 0,
        ]);

        $this->getJson('/api/v1/admin/comments?status=pending&search=Pending')
            ->assertOk()
            ->assertJsonPath('data.0.id', $comment->id)
            ->assertJsonPath('meta.status_counts.pending', 1);

        $this->getJson("/api/v1/admin/comments/{$comment->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $comment->id);

        $this->putJson("/api/v1/admin/comments/{$comment->id}", [
            'content' => 'Updated content',
            'status' => '1',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.approved', '1');

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Updated content',
            'approved' => '1',
        ]);

        $this->postJson("/api/v1/admin/comments/{$comment->id}/unapprove")
            ->assertOk()
            ->assertJsonPath('data.approved', '0');
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'approved' => '0']);

        $this->postJson("/api/v1/admin/comments/{$comment->id}/spam")
            ->assertOk()
            ->assertJsonPath('data.approved', 'spam');
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'approved' => 'spam']);

        $this->postJson("/api/v1/admin/comments/{$comment->id}/trash")
            ->assertOk()
            ->assertJsonPath('data.approved', 'trash');
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'approved' => 'trash']);

        $this->postJson("/api/v1/admin/comments/{$comment->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.approved', '0');
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'approved' => '0']);

        $replyResponse = $this->postJson("/api/v1/admin/comments/{$comment->id}/reply", [
            'content' => 'Official admin reply',
        ]);

        $replyResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.parent_id', $comment->id)
            ->assertJsonPath('data.approved', '1');
        $replyId = (int) $replyResponse->json('data.id');

        $this->postJson('/api/v1/admin/comments/bulk', [
            'action' => 'spam',
            'comment_ids' => [$bulkTarget->id],
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.affected', 1);

        $this->assertDatabaseHas('comments', [
            'id' => $bulkTarget->id,
            'approved' => 'spam',
        ]);

        $this->deleteJson("/api/v1/admin/comments/{$comment->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        $this->assertDatabaseMissing('comments', ['id' => $replyId]);
    }

    public function test_comment_validation_returns_admin_error_shape(): void
    {
        $this->authenticateWithCapabilities(['moderate_comments']);

        $this->postJson('/api/v1/admin/comments/bulk', [
            'action' => 'invalid',
            'comment_ids' => [],
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

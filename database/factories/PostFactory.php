<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = fake()->sentence();
        $now = now();

        return [
            'author_id' => User::factory(),
            'post_date' => $now,
            'post_date_gmt' => $now,
            'content' => fake()->paragraphs(3, true),
            'title' => $title,
            'excerpt' => fake()->sentence(),
            'status' => 'publish',
            'comment_status' => 'open',
            'ping_status' => 'open',
            'password' => '',
            'slug' => Str::slug($title),
            'post_modified' => $now,
            'post_modified_gmt' => $now,
            'content_filtered' => '',
            'parent_id' => 0,
            'guid' => '',
            'menu_order' => 0,
            'type' => 'post',
            'mime_type' => '',
            'comment_count' => 0,
        ];
    }

    /**
     * Create a page instead of a post.
     */
    public function page(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'page',
        ]);
    }

    /**
     * Create a draft post.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Create a trashed post.
     */
    public function trashed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'trash',
        ]);
    }

    /**
     * Create a revision.
     */
    public function revision(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'revision',
        ]);
    }
}

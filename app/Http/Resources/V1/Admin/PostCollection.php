<?php

namespace App\Http\Resources\V1\Admin;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Paginated post collection with status count metadata.
 * Mirrors WP admin Posts list with status tabs: All(12) | Published(8) | Draft(3) | ...
 */
class PostCollection extends ResourceCollection
{
    public $collects = PostResource::class;

    /**
     * The post type to count statuses for (set by controller).
     */
    public string $postType = 'post';

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    public function with(Request $request): array
    {
        return [
            'meta' => [
                'status_counts' => $this->getStatusCounts(),
            ],
        ];
    }

    private function getStatusCounts(): array
    {
        $query = Post::ofType($this->postType)->where('type', '!=', 'revision');

        return [
            'all' => (clone $query)->where('status', '!=', 'trash')->count(),
            'publish' => (clone $query)->where('status', 'publish')->count(),
            'draft' => (clone $query)->where('status', 'draft')->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'trash' => (clone $query)->where('status', 'trash')->count(),
        ];
    }
}

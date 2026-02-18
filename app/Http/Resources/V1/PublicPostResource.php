<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Public-facing post representation — stripped down for visitors.
 * No admin-only fields (password, comment_status, menu_order etc.).
 */
#[OA\Schema(
    schema: "PublicPost",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 42),
        new OA\Property(property: "title", type: "string", example: "Hello World"),
        new OA\Property(property: "slug", type: "string", example: "hello-world"),
        new OA\Property(property: "content", type: "string"),
        new OA\Property(property: "excerpt", type: "string"),
        new OA\Property(property: "author", type: "object", properties: [
            new OA\Property(property: "id", type: "integer"),
            new OA\Property(property: "display_name", type: "string"),
        ]),
        new OA\Property(property: "categories", type: "array", items: new OA\Items(type: "object", properties: [
            new OA\Property(property: "name", type: "string"),
            new OA\Property(property: "slug", type: "string"),
        ])),
        new OA\Property(property: "tags", type: "array", items: new OA\Items(type: "object", properties: [
            new OA\Property(property: "name", type: "string"),
            new OA\Property(property: "slug", type: "string"),
        ])),
        new OA\Property(property: "featured_image_id", type: "integer", nullable: true),
        new OA\Property(property: "published_at", type: "string", format: "date-time"),
        new OA\Property(property: "comment_count", type: "integer"),
    ]
)]
class PublicPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $metaMap = $this->whenLoaded('meta', function () {
            return $this->meta->pluck('meta_value', 'meta_key');
        }, collect());

        $categories = $this->whenLoaded('taxonomies', function () {
            return $this->taxonomies
                ->where('taxonomy', 'category')
                ->map(fn($tt) => [
                    'name' => $tt->term->name ?? '',
                    'slug' => $tt->term->slug ?? '',
                ]);
        }, []);

        $tags = $this->whenLoaded('taxonomies', function () {
            return $this->taxonomies
                ->where('taxonomy', 'post_tag')
                ->map(fn($tt) => [
                    'name' => $tt->term->name ?? '',
                    'slug' => $tt->term->slug ?? '',
                ]);
        }, []);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'author' => $this->whenLoaded('author', fn() => [
                'id' => $this->author->id,
                'display_name' => $this->author->display_name ?? $this->author->name,
            ]),
            'categories' => collect($categories)->values(),
            'tags' => collect($tags)->values(),
            'featured_image_id' => $metaMap['_thumbnail_id'] ?? null,
            'published_at' => $this->post_date?->toIso8601String(),
            'comment_count' => $this->comment_count ?? 0,
        ];
    }
}

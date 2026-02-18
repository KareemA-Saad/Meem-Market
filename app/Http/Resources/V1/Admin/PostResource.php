<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Full post representation with embedded relations.
 */
#[OA\Schema(
    schema: "AdminPost",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 42),
        new OA\Property(property: "title", type: "string", example: "Hello World"),
        new OA\Property(property: "slug", type: "string", example: "hello-world"),
        new OA\Property(property: "content", type: "string"),
        new OA\Property(property: "excerpt", type: "string"),
        new OA\Property(property: "status", type: "string", enum: ["publish", "draft", "pending", "private", "trash", "future"]),
        new OA\Property(property: "type", type: "string", enum: ["post", "page"]),
        new OA\Property(property: "author", ref: "#/components/schemas/AdminUser", nullable: true),
        new OA\Property(property: "comment_status", type: "string"),
        new OA\Property(property: "password", type: "string", nullable: true),
        new OA\Property(property: "parent_id", type: "integer", nullable: true),
        new OA\Property(property: "menu_order", type: "integer"),
        new OA\Property(property: "featured_image_id", type: "integer", nullable: true),
        new OA\Property(property: "template", type: "string", nullable: true),
        new OA\Property(property: "categories", type: "array", items: new OA\Items(type: "object")),
        new OA\Property(property: "tags", type: "array", items: new OA\Items(type: "object")),
        new OA\Property(property: "post_date", type: "string", format: "date-time", nullable: true),
        new OA\Property(property: "post_modified", type: "string", format: "date-time", nullable: true),
        new OA\Property(property: "comment_count", type: "integer"),
    ]
)]
class PostResource extends JsonResource
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
                    'id' => $tt->id,
                    'name' => $tt->term->name ?? '',
                    'slug' => $tt->term->slug ?? '',
                ]);
        }, []);

        $tags = $this->whenLoaded('taxonomies', function () {
            return $this->taxonomies
                ->where('taxonomy', 'post_tag')
                ->map(fn($tt) => [
                    'id' => $tt->id,
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
            'status' => $this->status,
            'type' => $this->type,
            'author' => $this->whenLoaded('author', fn() => new UserResource($this->author)),
            'comment_status' => $this->comment_status,
            'password' => $this->password ?: null,
            'parent_id' => $this->parent_id,
            'menu_order' => $this->menu_order,
            'featured_image_id' => $metaMap['_thumbnail_id'] ?? null,
            'template' => $metaMap['_wp_page_template'] ?? null,
            'categories' => collect($categories)->values(),
            'tags' => collect($tags)->values(),
            'post_date' => $this->post_date?->toIso8601String(),
            'post_modified' => $this->post_modified?->toIso8601String(),
            'comment_count' => $this->comment_count ?? 0,
        ];
    }
}

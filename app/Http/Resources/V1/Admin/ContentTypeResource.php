<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * JSON representation of a custom post type or taxonomy definition.
 */
#[OA\Schema(
    schema: "AdminContentType",
    properties: [
        new OA\Property(property: "slug", type: "string", example: "product"),
        new OA\Property(property: "label", type: "string", example: "Products"),
        new OA\Property(property: "singular_label", type: "string", example: "Product"),
        new OA\Property(property: "public", type: "boolean", example: true),
        new OA\Property(property: "hierarchical", type: "boolean", example: false),
        new OA\Property(property: "has_archive", type: "boolean", example: true),
        new OA\Property(property: "supports", type: "array", items: new OA\Items(type: "string")),
        new OA\Property(property: "taxonomies", type: "array", items: new OA\Items(type: "string")),
        new OA\Property(property: "menu_icon", type: "string", nullable: true),
        new OA\Property(property: "menu_position", type: "integer", nullable: true),
    ]
)]
class ContentTypeResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // $this->resource is an associative array, not a Model
        $data = $this->resource;

        return [
            'slug' => $data['slug'] ?? '',
            'label' => $data['label'] ?? '',
            'singular_label' => $data['singular_label'] ?? '',
            'labels' => $data['labels'] ?? [],
            'public' => (bool) ($data['public'] ?? true),
            'show_ui' => (bool) ($data['show_ui'] ?? true),
            'has_archive' => (bool) ($data['has_archive'] ?? false),
            'hierarchical' => (bool) ($data['hierarchical'] ?? false),
            'supports' => $data['supports'] ?? ['title', 'editor'],
            'taxonomies' => $data['taxonomies'] ?? [],
            'menu_icon' => $data['menu_icon'] ?? null,
            'menu_position' => $data['menu_position'] ?? null,
        ];
    }
}

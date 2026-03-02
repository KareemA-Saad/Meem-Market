<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * JSON representation of a single menu item (nav_menu_item post).
 */
#[OA\Schema(
    schema: "AdminMenuItem",
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "title", type: "string"),
        new OA\Property(property: "url", type: "string"),
        new OA\Property(property: "type", type: "string", description: "custom, post_type, taxonomy"),
        new OA\Property(property: "object_id", type: "integer", nullable: true),
        new OA\Property(property: "parent_item_id", type: "integer", nullable: true),
        new OA\Property(property: "position", type: "integer"),
        new OA\Property(property: "target", type: "string", nullable: true),
        new OA\Property(property: "css_classes", type: "string", nullable: true),
        new OA\Property(property: "children", type: "array", items: new OA\Items(ref: "#/components/schemas/AdminMenuItem")),
    ]
)]
class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $metaMap = $this->whenLoaded('meta', function () {
            return $this->meta->pluck('meta_value', 'meta_key');
        }, collect());

        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $metaMap['_menu_item_url'] ?? '',
            'type' => $metaMap['_menu_item_type'] ?? 'custom',
            'object_id' => (int) ($metaMap['_menu_item_object_id'] ?? 0) ?: null,
            'parent_item_id' => $this->parent_id ?: null,
            'position' => $this->menu_order,
            'target' => $metaMap['_menu_item_target'] ?? null,
            'css_classes' => $metaMap['_menu_item_classes'] ?? null,
        ];
    }
}

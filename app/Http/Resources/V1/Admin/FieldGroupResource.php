<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * JSON representation of an ACF-style field group with its child fields.
 */
#[OA\Schema(
    schema: "AdminFieldGroup",
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "title", type: "string"),
        new OA\Property(property: "status", type: "string", enum: ["publish", "draft"]),
        new OA\Property(property: "position", type: "string", nullable: true),
        new OA\Property(property: "style", type: "string", nullable: true),
        new OA\Property(property: "label_placement", type: "string", nullable: true),
        new OA\Property(property: "location_rules", type: "array", items: new OA\Items(type: "object")),
        new OA\Property(property: "fields", type: "array", items: new OA\Items(type: "object")),
        new OA\Property(property: "menu_order", type: "integer"),
    ]
)]
class FieldGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $metaMap = $this->whenLoaded('meta', function () {
            return $this->meta->pluck('meta_value', 'meta_key');
        }, collect());

        $fields = $this->whenLoaded('children', function () {
            return $this->children->map(function ($field) {
                $fieldMeta = $field->meta->pluck('meta_value', 'meta_key');

                return [
                    'id' => $field->id,
                    'label' => $field->title,
                    'name' => $field->slug,
                    'type' => $fieldMeta['field_type'] ?? 'text',
                    'instructions' => $fieldMeta['instructions'] ?? '',
                    'required' => (bool) ($fieldMeta['required'] ?? false),
                    'default_value' => $fieldMeta['default_value'] ?? null,
                    'options' => json_decode($fieldMeta['options'] ?? '{}', true),
                    'menu_order' => $field->menu_order,
                ];
            })->sortBy('menu_order')->values();
        }, []);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'position' => $metaMap['position'] ?? 'normal',
            'style' => $metaMap['style'] ?? 'default',
            'label_placement' => $metaMap['label_placement'] ?? 'top',
            'location_rules' => json_decode($metaMap['location_rules'] ?? '[]', true),
            'fields' => $fields,
            'menu_order' => $this->menu_order,
        ];
    }
}

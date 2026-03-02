<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * JSON representation of a navigation menu with its items as a tree.
 */
#[OA\Schema(
    schema: "AdminMenu",
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "name", type: "string"),
        new OA\Property(property: "slug", type: "string"),
        new OA\Property(property: "description", type: "string"),
        new OA\Property(property: "items", type: "array", items: new OA\Items(ref: "#/components/schemas/AdminMenuItem")),
    ]
)]
class MenuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // $this wraps a TermTaxonomy (taxonomy='nav_menu')
        return [
            'id' => $this->id,
            'name' => $this->term?->name,
            'slug' => $this->term?->slug,
            'description' => $this->description ?? '',
            'count' => $this->count,
        ];
    }
}

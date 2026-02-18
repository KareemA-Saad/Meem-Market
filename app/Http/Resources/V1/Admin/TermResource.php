<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AdminTerm",
    title: "Admin Term Resource",
    description: "Term (category/tag/custom taxonomy) with full details",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "term_id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Technology"),
        new OA\Property(property: "slug", type: "string", example: "technology"),
        new OA\Property(property: "taxonomy", type: "string", example: "category"),
        new OA\Property(property: "description", type: "string", example: "Articles about technology"),
        new OA\Property(property: "parent", type: "integer", nullable: true, example: null),
        new OA\Property(property: "count", type: "integer", example: 5, description: "Number of posts using this term"),
        new OA\Property(property: "parent_term", type: "object", nullable: true, description: "Parent term details if hierarchical"),
    ]
)]
class TermResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // $this is TermTaxonomy instance
        return [
            'id' => $this->id,
            'term_id' => $this->term_id,
            'name' => $this->term?->name,
            'slug' => $this->term?->slug,
            'taxonomy' => $this->taxonomy,
            'description' => $this->description ?? '',
            'parent' => $this->parent ?: null,
            'count' => $this->count,
            'parent_term' => $this->when(
                $this->parent && $this->parentTerm,
                fn() => [
                    'id' => $this->parentTerm?->id,
                    'name' => $this->parentTerm?->term?->name,
                    'slug' => $this->parentTerm?->term?->slug,
                ]
            ),
        ];
    }
}

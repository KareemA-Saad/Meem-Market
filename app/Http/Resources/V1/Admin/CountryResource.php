<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AdminCountry",
    title: "Admin Country",
    description: "Admin country representation",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name_ar", type: "string", example: "السعودية"),
        new OA\Property(property: "name_en", type: "string", nullable: true, example: "Saudi Arabia"),
        new OA\Property(property: "slug", type: "string", example: "saudi-arabia"),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "sort_order", type: "integer", example: 1),
        new OA\Property(property: "branches_count", type: "integer", example: 4),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
class CountryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'slug' => $this->slug,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'branches_count' => $this->whenCounted('branches'),
            'branches' => BranchResource::collection($this->whenLoaded('branches')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AdminCareer",
    title: "Admin Career",
    description: "Admin career representation",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "title", type: "string", example: "Store Cashier"),
        new OA\Property(property: "slug", type: "string", example: "store-cashier"),
        new OA\Property(property: "location", type: "string", nullable: true, example: "Riyadh"),
        new OA\Property(property: "type", type: "string", nullable: true, example: "Full Time"),
        new OA\Property(property: "description", type: "string"),
        new OA\Property(property: "requirements", type: "string", nullable: true),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "sort_order", type: "integer", example: 1),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
class CareerResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'location' => $this->location,
            'type' => $this->type,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

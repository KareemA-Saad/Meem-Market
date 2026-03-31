<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AdminOffer",
    title: "Admin Offer",
    description: "Admin offer representation",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "offer_category_id", type: "integer", example: 1),
        new OA\Property(property: "title", type: "string", nullable: true, example: "Buy 1 Get 1 Free"),
        new OA\Property(property: "image", type: "string", example: "offers/2026/03/uuid.jpg"),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "sort_order", type: "integer", example: 0),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
class OfferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'offer_category_id' => $this->offer_category_id,
            'title'             => $this->title,
            'image'             => $this->image,
            'is_active'         => $this->is_active,
            'sort_order'        => $this->sort_order,
            'offer_category'    => new OfferCategoryResource($this->whenLoaded('offerCategory')),
            'created_at'        => $this->created_at?->toIso8601String(),
            'updated_at'        => $this->updated_at?->toIso8601String(),
        ];
    }
}

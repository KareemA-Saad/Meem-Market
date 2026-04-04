<?php

namespace App\Http\Resources\V1\Admin;

use App\Http\Resources\V1\BranchResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AdminOfferCategory",
    title: "Admin Offer Category",
    description: "Admin offer category representation",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "branch_id", type: "integer", example: 1),
        new OA\Property(property: "title", type: "string", example: "Summer Offers 2026"),
        new OA\Property(property: "slug", type: "string", example: "summer-offers-2026"),
        new OA\Property(property: "cover_image", type: "string", nullable: true, example: "offers/2026/03/uuid.jpg"),
        new OA\Property(property: "start_date", type: "string", format: "date", nullable: true, example: "2026-06-01"),
        new OA\Property(property: "end_date", type: "string", format: "date", nullable: true, example: "2026-08-31"),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "is_expired", type: "boolean", example: false),
        new OA\Property(property: "sort_order", type: "integer", example: 0),
        new OA\Property(property: "offers_count", type: "integer", example: 12),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
class OfferCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'branch_id'    => $this->branch_id,
            'title'        => $this->title,
            'slug'         => $this->slug,
            'cover_image'  => $this->cover_image,
            'start_date'   => $this->start_date?->toDateString(),
            'end_date'     => $this->end_date?->toDateString(),
            'is_active'    => $this->is_active,
            'is_expired'   => $this->is_expired,
            'sort_order'   => $this->sort_order,
            'offers_count' => $this->whenCounted('offers'),
            'branch'       => new BranchResource($this->whenLoaded('branch')),
            'offers'       => OfferResource::collection($this->whenLoaded('offers')),
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}

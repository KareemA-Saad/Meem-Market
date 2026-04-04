<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AdminBranch",
    title: "Admin Branch",
    description: "Admin branch representation",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "country_id", type: "integer", example: 1),
        new OA\Property(property: "name_ar", type: "string", example: "الأحساء"),
        new OA\Property(property: "name_en", type: "string", nullable: true, example: "Al Ahsa"),
        new OA\Property(property: "slug", type: "string", example: "ahsa"),
        new OA\Property(property: "address", type: "string", nullable: true, example: "Al Ahsa, Saudi Arabia"),
        new OA\Property(property: "google_maps_url", type: "string", nullable: true, example: "https://maps.app.goo.gl/example"),
        new OA\Property(property: "latitude", type: "number", format: "double", nullable: true, example: 25.4017469),
        new OA\Property(property: "longitude", type: "number", format: "double", nullable: true, example: 49.5600663),
        new OA\Property(property: "phone", type: "string", nullable: true, example: "0551297970"),
        new OA\Property(property: "unified_phone", type: "string", nullable: true, example: "920010937"),
        new OA\Property(property: "social_links", type: "object", nullable: true),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "sort_order", type: "integer", example: 2),
        new OA\Property(property: "offer_categories_count", type: "integer", example: 7),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
class BranchResource extends JsonResource
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
            'country_id' => $this->country_id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'slug' => $this->slug,
            'address' => $this->address,
            'google_maps_url' => $this->google_maps_url,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'phone' => $this->phone,
            'unified_phone' => $this->unified_phone,
            'social_links' => $this->social_links,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'offer_categories_count' => $this->whenCounted('offerCategories'),
            'country' => new CountryResource($this->whenLoaded('country')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

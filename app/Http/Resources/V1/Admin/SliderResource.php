<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AdminSlider",
    title: "Admin Slider",
    description: "Admin slider representation",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "title", type: "string", nullable: true, example: "Summer Banner"),
        new OA\Property(property: "image", type: "string", example: "http://localhost/storage/uploads/2026/04/banner.webp"),
        new OA\Property(property: "media_type", type: "string", enum: ["image", "video"], example: "image"),
        new OA\Property(property: "link", type: "string", nullable: true, example: "https://meem.market/offers"),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "sort_order", type: "integer", example: 1),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
class SliderResource extends JsonResource
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
            'image' => $this->image,
            'media_type' => $this->media_type ?? 'image',
            'link' => $this->link,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

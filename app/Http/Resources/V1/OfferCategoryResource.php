<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'id' => $this->id,
            'title' => $this->title, // Translatable if needed, but schema has single title column + model logic might differ
            'slug' => $this->slug,
            'cover_image' => $this->cover_image,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => $this->is_active,
            'is_expired' => $this->is_expired, // Accessor
            'offers_count' => $this->whenCounted('offers'),
            'offers' => OfferResource::collection($this->whenLoaded('offers')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
        ];
    }
}

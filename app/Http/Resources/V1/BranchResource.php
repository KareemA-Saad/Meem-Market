<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'phone' => $this->phone,
            'unified_phone' => $this->unified_phone,
            'social_links' => $this->social_links,
            'offer_categories' => OfferCategoryResource::collection($this->whenLoaded('offerCategories')),
        ];
    }
}

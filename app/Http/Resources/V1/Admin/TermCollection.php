<?php

namespace App\Http\Resources\V1\Admin;

use App\Models\TermTaxonomy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TermCollection extends ResourceCollection
{
    public string $taxonomy = 'category';

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->total(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
                'taxonomy' => $this->taxonomy,
            ],
        ];
    }
}

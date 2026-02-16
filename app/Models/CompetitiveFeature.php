<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitiveFeature extends Model
{
    protected $fillable = [
        'title',
        'description',
        'icon',
        'sort_order',
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}

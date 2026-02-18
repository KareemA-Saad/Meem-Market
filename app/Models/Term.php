<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Term extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'term_group',
    ];

    protected function casts(): array
    {
        return [
            'term_group' => 'integer',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────

    public function taxonomy(): HasOne
    {
        return $this->hasOne(TermTaxonomy::class);
    }

    public function meta(): HasMany
    {
        return $this->hasMany(TermMeta::class);
    }
}

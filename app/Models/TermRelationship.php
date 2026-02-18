<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot model for the many-to-many between posts and term_taxonomy.
 * Uses a composite primary key (object_id + term_taxonomy_id).
 */
class TermRelationship extends Pivot
{
    public $timestamps = false;

    public $incrementing = false;

    protected $table = 'term_relationships';

    protected $fillable = [
        'object_id',
        'term_taxonomy_id',
        'term_order',
    ];

    protected function casts(): array
    {
        return [
            'term_order' => 'integer',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────

    public function taxonomy(): BelongsTo
    {
        return $this->belongsTo(TermTaxonomy::class, 'term_taxonomy_id');
    }
}

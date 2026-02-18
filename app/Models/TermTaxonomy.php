<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TermTaxonomy extends Model
{
    public $timestamps = false;

    protected $table = 'term_taxonomy';

    protected $fillable = [
        'term_id',
        'taxonomy',
        'description',
        'parent',
        'count',
    ];

    protected function casts(): array
    {
        return [
            'parent' => 'integer',
            'count' => 'integer',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Posts linked through the term_relationships pivot.
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(
            Post::class,
            'term_relationships',
            'term_taxonomy_id',
            'object_id',
        )->withPivot('term_order');
    }

    /**
     * Parent term taxonomy (for hierarchical taxonomies).
     */
    public function parentTerm(): BelongsTo
    {
        return $this->belongsTo(TermTaxonomy::class, 'parent', 'id');
    }

    /**
     * Child term taxonomies (for hierarchical taxonomies).
     */
    public function children()
    {
        return $this->hasMany(TermTaxonomy::class, 'parent', 'id');
    }
}

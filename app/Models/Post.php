<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'author_id',
        'post_date',
        'post_date_gmt',
        'content',
        'title',
        'excerpt',
        'status',
        'comment_status',
        'ping_status',
        'password',
        'slug',
        'post_modified',
        'post_modified_gmt',
        'content_filtered',
        'parent_id',
        'guid',
        'menu_order',
        'type',
        'mime_type',
        'comment_count',
    ];

    protected function casts(): array
    {
        return [
            'post_date' => 'datetime',
            'post_date_gmt' => 'datetime',
            'post_modified' => 'datetime',
            'post_modified_gmt' => 'datetime',
            'parent_id' => 'integer',
            'menu_order' => 'integer',
            'comment_count' => 'integer',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function meta(): HasMany
    {
        return $this->hasMany(PostMeta::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Many-to-many through term_relationships → term_taxonomy.
     * Enables: $post->taxonomies (returns TermTaxonomy pivots).
     */
    public function taxonomies(): BelongsToMany
    {
        return $this->belongsToMany(
            TermTaxonomy::class,
            'term_relationships',
            'object_id',
            'term_taxonomy_id',
        )->withPivot('term_order');
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeOfStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'publish');
    }
}

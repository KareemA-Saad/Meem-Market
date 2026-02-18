<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'author_name',
        'author_email',
        'author_url',
        'author_ip',
        'comment_date',
        'comment_date_gmt',
        'content',
        'karma',
        'approved',
        'agent',
        'type',
        'parent_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'comment_date' => 'datetime',
            'comment_date_gmt' => 'datetime',
            'karma' => 'integer',
            'parent_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function meta(): HasMany
    {
        return $this->hasMany(CommentMeta::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approved', '1');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('approved', '0');
    }

    public function scopeSpam(Builder $query): Builder
    {
        return $query->where('approved', 'spam');
    }
}

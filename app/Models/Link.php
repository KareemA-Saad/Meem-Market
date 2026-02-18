<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Link extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'url',
        'name',
        'image',
        'target',
        'description',
        'visible',
        'owner_id',
        'rating',
        'updated_at',
        'rel',
        'notes',
        'rss',
    ];

    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
            'rating' => 'integer',
            'owner_id' => 'integer',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}

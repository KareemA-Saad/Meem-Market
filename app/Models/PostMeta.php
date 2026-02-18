<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostMeta extends Model
{
    public $timestamps = false;

    protected $table = 'post_meta';

    protected $primaryKey = 'meta_id';

    protected $fillable = [
        'post_id',
        'meta_key',
        'meta_value',
    ];

    // ─── Relationships ───────────────────────────────────────────

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentMeta extends Model
{
    public $timestamps = false;

    protected $table = 'comment_meta';

    protected $primaryKey = 'meta_id';

    protected $fillable = [
        'comment_id',
        'meta_key',
        'meta_value',
    ];

    // ─── Relationships ───────────────────────────────────────────

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }
}

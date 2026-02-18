<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMeta extends Model
{
    public $timestamps = false;

    protected $table = 'user_meta';

    protected $primaryKey = 'umeta_id';

    protected $fillable = [
        'user_id',
        'meta_key',
        'meta_value',
    ];

    // ─── Relationships ───────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

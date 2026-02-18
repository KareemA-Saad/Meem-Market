<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermMeta extends Model
{
    public $timestamps = false;

    protected $table = 'term_meta';

    protected $primaryKey = 'meta_id';

    protected $fillable = [
        'term_id',
        'meta_key',
        'meta_value',
    ];

    // ─── Relationships ───────────────────────────────────────────

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }
}

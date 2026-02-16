<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
    ];

    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Get all settings for a group as key => value pairs.
     */
    public static function getGroup(string $group): array
    {
        return static::byGroup($group)
            ->pluck('value', 'key')
            ->toArray();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CMS/admin options — separate from the storefront's `settings` table.
 * Provides static helpers for quick get/set/delete access.
 */
class Option extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'value',
        'autoload',
    ];

    // ─── Static Helpers ──────────────────────────────────────────

    /**
     * Get an option value by name. Returns $default if not found.
     */
    public static function get(string $name, mixed $default = null): mixed
    {
        $option = static::where('name', $name)->first();

        return $option?->value ?? $default;
    }

    /**
     * Set an option value, creating it if it doesn't exist.
     */
    public static function set(string $name, mixed $value, string $autoload = 'yes'): void
    {
        static::updateOrCreate(
            ['name' => $name],
            ['value' => is_array($value) ? json_encode($value) : $value, 'autoload' => $autoload],
        );
    }

    /**
     * Delete an option by name.
     */
    public static function remove(string $name): bool
    {
        return (bool) static::where('name', $name)->delete();
    }
}

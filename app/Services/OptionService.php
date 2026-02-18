<?php

namespace App\Services;

use App\Models\Option;
use Illuminate\Support\Collection;

/**
 * Centralised access to the CMS options table with request-level caching.
 *
 * Why request caching? Options like `user_roles` or `blogname` may be read
 * dozens of times per request (middleware, controllers, resources). Hitting
 * the DB once per option would be wasteful, so we cache in a static array
 * that lives only for the duration of the current request.
 */
class OptionService
{
    /**
     * @var array<string, mixed> Request-scoped cache of option values.
     */
    private static array $cache = [];

    private static bool $autoloadedLoaded = false;

    /**
     * Get an option value by name.
     */
    public function get(string $name, mixed $default = null): mixed
    {
        // Ensure all autoloaded options are in cache on first access
        if (!self::$autoloadedLoaded) {
            $this->loadAutoloaded();
        }

        if (array_key_exists($name, self::$cache)) {
            return self::$cache[$name];
        }

        // Option wasn't autoloaded — fetch individually
        $option = Option::where('name', $name)->first();

        if (!$option) {
            return $default;
        }

        self::$cache[$name] = $option->value;

        return $option->value;
    }

    /**
     * Set an option value, creating it if it doesn't exist.
     */
    public function set(string $name, mixed $value, string $autoload = 'yes'): void
    {
        $serialized = is_array($value) || is_object($value) ? json_encode($value) : $value;

        Option::updateOrCreate(
            ['name' => $name],
            ['value' => $serialized, 'autoload' => $autoload],
        );

        self::$cache[$name] = $serialized;
    }

    /**
     * Delete an option by name.
     */
    public function delete(string $name): void
    {
        Option::where('name', $name)->delete();
        unset(self::$cache[$name]);
    }

    /**
     * Bulk-load all autoloaded options into cache.
     * Called once per request on first get() call.
     */
    private function loadAutoloaded(): void
    {
        $options = Option::where('autoload', 'yes')->pluck('value', 'name');

        foreach ($options as $name => $value) {
            self::$cache[$name] = $value;
        }

        self::$autoloadedLoaded = true;
    }

    /**
     * Clear the request cache. Useful in tests or long-running processes.
     */
    public static function clearCache(): void
    {
        self::$cache = [];
        self::$autoloadedLoaded = false;
    }
}

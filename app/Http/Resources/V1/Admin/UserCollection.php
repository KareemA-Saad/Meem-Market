<?php

namespace App\Http\Resources\V1\Admin;

use App\Models\UserMeta;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * UserCollection — paginated user listing with role count metadata.
 *
 * Adds a `role_counts` meta field that mirrors the WP admin Users
 * screen's role filter tabs: All(12) | Administrator(2) | Editor(3) | ...
 */
class UserCollection extends ResourceCollection
{
    public $collects = UserResource::class;

    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    /**
     * Add role counts to the response metadata.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'role_counts' => $this->getRoleCounts(),
            ],
        ];
    }

    /**
     * Count users per role by reading wp_capabilities from user_meta.
     *
     * @return array<string, int>
     */
    private function getRoleCounts(): array
    {
        $allCapabilities = UserMeta::where('meta_key', 'wp_capabilities')
            ->pluck('meta_value');

        $counts = ['all' => $allCapabilities->count()];

        foreach ($allCapabilities as $capJson) {
            $decoded = json_decode($capJson, true);

            if (!is_array($decoded)) {
                continue;
            }

            $role = array_key_first($decoded);

            if ($role) {
                $counts[$role] = ($counts[$role] ?? 0) + 1;
            }
        }

        return $counts;
    }
}

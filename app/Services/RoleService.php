<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserMeta;

/**
 * WP-style role and capability management.
 *
 * Roles and capabilities are stored in the `options` table under the key
 * `user_roles` as a JSON map: { "administrator": { "name": "...", "capabilities": {...} }, ... }
 *
 * Each user's assigned role is stored in `user_meta` under the key
 * `wp_capabilities` as a JSON object: { "administrator": true }
 */
class RoleService
{
    public function __construct(
        private readonly OptionService $optionService,
    ) {}

    /**
     * Get all defined roles from the options table.
     *
     * @return array<string, array{name: string, capabilities: array<string, bool>}>
     */
    public function getRoles(): array
    {
        $rolesJson = $this->optionService->get('user_roles', '{}');

        return json_decode($rolesJson, true) ?: [];
    }

    /**
     * Get a single role definition by slug.
     *
     * @return array{name: string, capabilities: array<string, bool>}|null
     */
    public function getRole(string $name): ?array
    {
        return $this->getRoles()[$name] ?? null;
    }

    /**
     * Check if a user has a specific capability through their assigned role.
     */
    public function userCan(User $user, string $capability): bool
    {
        $roleName = $this->getUserRole($user);

        if (!$roleName) {
            return false;
        }

        $role = $this->getRole($roleName);

        if (!$role) {
            return false;
        }

        return !empty($role['capabilities'][$capability]);
    }

    /**
     * Get the user's current role slug from user_meta.
     */
    public function getUserRole(User $user): ?string
    {
        $meta = UserMeta::where('user_id', $user->id)
            ->where('meta_key', 'wp_capabilities')
            ->first();

        if (!$meta?->meta_value) {
            return null;
        }

        $capabilities = json_decode($meta->meta_value, true);

        if (!is_array($capabilities)) {
            return null;
        }

        // WP stores roles as { "administrator": true } — we return the first key
        return array_key_first($capabilities);
    }

    /**
     * Get all capabilities for a user based on their role.
     *
     * @return array<string, bool>
     */
    public function getUserCapabilities(User $user): array
    {
        $roleName = $this->getUserRole($user);

        if (!$roleName) {
            return [];
        }

        $role = $this->getRole($roleName);

        return $role['capabilities'] ?? [];
    }

    /**
     * Set the user's role by writing to user_meta.
     */
    public function setUserRole(User $user, string $role): void
    {
        // Validate the role exists
        if (!$this->getRole($role)) {
            throw new \InvalidArgumentException("Role '{$role}' does not exist.");
        }

        UserMeta::updateOrCreate(
            ['user_id' => $user->id, 'meta_key' => 'wp_capabilities'],
            ['meta_value' => json_encode([$role => true])],
        );
    }
}

<?php

use App\Models\Option;
use Illuminate\Database\Migrations\Migration;

/**
 * Adds retail-specific admin capabilities to the role system.
 *
 * Sprint 1: manage_offers, manage_offer_categories
 * These are injected into the existing user_roles option for
 * administrator and editor roles.
 */
return new class extends Migration {
    private array $capabilities = [
        'manage_offers',
        'manage_offer_categories',
    ];

    private array $roles = ['administrator', 'editor'];

    public function up(): void
    {
        $option = Option::where('name', 'user_roles')->first();

        if (!$option) {
            return;
        }

        $roles = json_decode($option->value, true);

        if (!is_array($roles)) {
            return;
        }

        foreach ($this->roles as $roleName) {
            if (!isset($roles[$roleName])) {
                continue;
            }

            foreach ($this->capabilities as $capability) {
                $roles[$roleName]['capabilities'][$capability] = true;
            }
        }

        $option->update(['value' => json_encode($roles)]);
    }

    public function down(): void
    {
        $option = Option::where('name', 'user_roles')->first();

        if (!$option) {
            return;
        }

        $roles = json_decode($option->value, true);

        if (!is_array($roles)) {
            return;
        }

        foreach ($this->roles as $roleName) {
            if (!isset($roles[$roleName])) {
                continue;
            }

            foreach ($this->capabilities as $capability) {
                unset($roles[$roleName]['capabilities'][$capability]);
            }
        }

        $option->update(['value' => json_encode($roles)]);
    }
};

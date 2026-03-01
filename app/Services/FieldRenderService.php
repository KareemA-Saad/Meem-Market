<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Collection;

/**
 * Resolves which ACF-style field groups apply to a given post,
 * and retrieves the custom field values from post_meta.
 *
 * Location rules follow ACF's OR/AND structure:
 * - Top-level array items are OR'd together (any group matching = show)
 * - Inner array items within a group are AND'd (all rules must match)
 *
 * Example location_rules JSON:
 * [
 *   [{"param":"post_type","operator":"==","value":"product"}],
 *   [{"param":"post_type","operator":"==","value":"service"},{"param":"page_template","operator":"==","value":"full-width"}]
 * ]
 */
class FieldRenderService
{
    /**
     * Get all published field groups whose location rules match the given post.
     */
    public function getFieldsForPost(Post $post): Collection
    {
        $fieldGroups = Post::where('type', 'acf-field-group')
            ->where('status', 'publish')
            ->with(['meta', 'children.meta'])
            ->orderBy('menu_order')
            ->get();

        return $fieldGroups->filter(fn(Post $group) => $this->matchesLocationRules($group, $post));
    }

    /**
     * Get the custom field key→value map from post_meta for display.
     *
     * Only returns meta keys that belong to registered field groups
     * applicable to this post, filtering out internal WP meta keys.
     *
     * @return array<string, mixed>
     */
    public function getFieldValues(Post $post): array
    {
        $applicableGroups = $this->getFieldsForPost($post);

        // Collect all field names from applicable groups
        $fieldNames = $applicableGroups
            ->flatMap(fn(Post $group) => $group->children->pluck('slug'))
            ->unique()
            ->toArray();

        if (empty($fieldNames)) {
            return [];
        }

        // Fetch post_meta values for those field names
        return $post->meta()
            ->whereIn('meta_key', $fieldNames)
            ->pluck('meta_value', 'meta_key')
            ->toArray();
    }

    /**
     * Check if a field group's location rules match a post.
     *
     * Top-level rule groups are OR'd; rules within a group are AND'd.
     */
    private function matchesLocationRules(Post $group, Post $post): bool
    {
        $rulesJson = $group->meta
            ->where('meta_key', 'location_rules')
            ->first()?->meta_value;

        if (!$rulesJson) {
            // No rules = show everywhere
            return true;
        }

        $ruleGroups = json_decode($rulesJson, true);

        if (!is_array($ruleGroups) || empty($ruleGroups)) {
            return true;
        }

        // OR: at least one rule group must match entirely
        foreach ($ruleGroups as $andGroup) {
            if ($this->allRulesMatch($andGroup, $post)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if ALL rules in an AND group match.
     */
    private function allRulesMatch(array $rules, Post $post): bool
    {
        foreach ($rules as $rule) {
            if (!$this->evaluateRule($rule, $post)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single location rule against a post.
     */
    private function evaluateRule(array $rule, Post $post): bool
    {
        $param = $rule['param'] ?? '';
        $operator = $rule['operator'] ?? '==';
        $value = $rule['value'] ?? '';

        $actual = match ($param) {
            'post_type' => $post->type,
            'post_status' => $post->status,
            'page_template' => $post->meta->where('meta_key', '_wp_page_template')->first()?->meta_value ?? 'default',
            default => null,
        };

        if ($actual === null) {
            return false;
        }

        return match ($operator) {
            '==' => $actual === $value,
            '!=' => $actual !== $value,
            default => false,
        };
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\V1\Admin\SettingsResource;
use App\Services\OptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Admin Settings API — GET/PUT for each WP-style settings section.
 *
 * Each section maps to a fixed set of option keys. Reads/writes go through
 * OptionService which wraps the `options` table with request-level caching.
 */
#[OA\Tag(name: "Admin Settings", description: "CMS settings management (general, writing, reading, discussion, media, permalinks, privacy)")]
class SettingsController extends ApiController
{
    /**
     * Option-key lists per section. Acts as the whitelist of what can be
     * read/written, and as the single source of truth for section shapes.
     */
    private const SECTION_KEYS = [
        'general' => [
            'blogname', 'blogdescription', 'siteurl', 'home', 'admin_email', 'users_can_register',
            'default_role', 'timezone_string', 'date_format', 'time_format', 'start_of_week',
        ],
        'writing' => [
            'default_category', 'default_post_format',
        ],
        'reading' => [
            'show_on_front', 'page_on_front', 'page_for_posts', 'posts_per_page', 'blog_public',
        ],
        'discussion' => [
            'default_comment_status', 'require_name_email', 'comment_registration', 'comment_moderation',
            'moderation_keys', 'disallowed_keys', 'comments_notify', 'show_avatars', 'avatar_default',
            'avatar_rating', 'close_comments_days_old', 'thread_comments', 'thread_comments_depth',
            'page_comments', 'comments_per_page', 'default_comments_page', 'comment_order',
        ],
        'media' => [
            'thumbnail_size_w', 'thumbnail_size_h', 'thumbnail_crop', 'medium_size_w', 'medium_size_h',
            'large_size_w', 'large_size_h', 'uploads_use_yearmonth_folders',
        ],
        'permalinks' => [
            'permalink_structure', 'category_base', 'tag_base',
        ],
        'privacy' => [
            'wp_page_for_privacy_policy',
        ],
    ];

    private const KEY_DEFAULTS = [
        'blogname'                    => 'MeemMark',
        'blogdescription'             => 'Just another site',
        'siteurl'                     => 'http://localhost:8000',
        'home'                        => 'http://localhost:8000',
        'admin_email'                 => 'admin@meemmark.com',
        'users_can_register'          => '0',
        'default_role'                => 'subscriber',
        'timezone_string'             => 'Asia/Riyadh',
        'date_format'                 => 'F j, Y',
        'time_format'                 => 'g:i a',
        'start_of_week'               => '1',
        'default_category'            => '1',
        'default_post_format'         => '0',
        'show_on_front'               => 'posts',
        'page_on_front'               => '0',
        'page_for_posts'              => '0',
        'posts_per_page'              => '10',
        'blog_public'                 => '1',
        'default_comment_status'      => 'open',
        'require_name_email'          => '1',
        'comment_registration'        => '0',
        'comment_moderation'          => '0',
        'moderation_keys'             => '',
        'disallowed_keys'             => '',
        'comments_notify'             => '1',
        'show_avatars'                => '1',
        'avatar_default'              => 'mystery',
        'avatar_rating'               => 'g',
        'close_comments_days_old'     => '0',
        'thread_comments'             => '0',
        'thread_comments_depth'       => '5',
        'page_comments'               => '0',
        'comments_per_page'           => '50',
        'default_comments_page'       => 'newest',
        'comment_order'               => 'asc',
        'thumbnail_size_w'            => '150',
        'thumbnail_size_h'            => '150',
        'thumbnail_crop'              => '1',
        'medium_size_w'               => '300',
        'medium_size_h'               => '300',
        'large_size_w'                => '1024',
        'large_size_h'                => '1024',
        'uploads_use_yearmonth_folders' => '1',
        'permalink_structure'         => '/%postname%/',
        'category_base'               => '',
        'tag_base'                    => '',
        'wp_page_for_privacy_policy'  => '0',
    ];

    private const BOOLEAN_KEYS = [
        'users_can_register', 'blog_public', 'require_name_email', 'comment_registration',
        'comment_moderation', 'comments_notify', 'show_avatars', 'thread_comments',
        'page_comments', 'thumbnail_crop', 'uploads_use_yearmonth_folders',
    ];

    private const INTEGER_KEYS = [
        'start_of_week', 'default_category', 'page_on_front', 'page_for_posts', 'posts_per_page',
        'close_comments_days_old', 'thread_comments_depth', 'comments_per_page',
        'thumbnail_size_w', 'thumbnail_size_h', 'medium_size_w', 'medium_size_h',
        'large_size_w', 'large_size_h', 'wp_page_for_privacy_policy',
    ];

    public function __construct(
        private readonly OptionService $optionService,
    ) {}

    // ─── GET Section ─────────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/admin/settings/{section}",
        operationId: "getSettings",
        summary: "Get settings for a section",
        description: "Returns all options for the specified section as typed key-value pairs.",
        tags: ["Admin Settings"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(
                name: "section",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string", enum: ["general", "writing", "reading", "discussion", "media", "permalinks", "privacy"])
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: "Settings object"),
            new OA\Response(response: 404, description: "Unknown section"),
        ]
    )]
    public function show(string $section): JsonResponse
    {
        if (!isset(self::SECTION_KEYS[$section])) {
            return $this->error("Unknown settings section: {$section}", 404);
        }

        return $this->success(new SettingsResource($this->readSection($section)));
    }

    // ─── PUT Section ─────────────────────────────────────────────

    #[OA\Put(
        path: "/api/v1/admin/settings/{section}",
        operationId: "updateSettings",
        summary: "Update settings for a section",
        description: "Updates one or more options within the specified section.",
        tags: ["Admin Settings"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(
                name: "section",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string", enum: ["general", "writing", "reading", "discussion", "media", "permalinks", "privacy"])
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                description: "Key-value pairs from the section's option list",
                additionalProperties: new OA\AdditionalProperties(type: "string")
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Settings updated"),
            new OA\Response(response: 404, description: "Unknown section"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function update(Request $request, string $section): JsonResponse
    {
        $keys = self::SECTION_KEYS[$section] ?? null;

        if (!$keys) {
            return $this->error("Unknown settings section: {$section}", 404);
        }

        $data = $request->only($keys);

        if (empty($data)) {
            return $this->error('No valid settings provided. Allowed keys: ' . implode(', ', $keys), 422);
        }

        return $this->success(new SettingsResource($this->writeSection($section, $data)));
    }

    // ═══════════════════════════════════════════════════════════
    //  Private Helpers
    // ═══════════════════════════════════════════════════════════

    /**
     * @return array<string, mixed>
     */
    private function readSection(string $section): array
    {
        $result = [];

        foreach (self::SECTION_KEYS[$section] as $key) {
            $value = $this->optionService->get($key, self::KEY_DEFAULTS[$key] ?? null);
            $result[$key] = $this->castOutputValue($key, $value);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function writeSection(string $section, array $payload): array
    {
        $allowed  = array_flip(self::SECTION_KEYS[$section]);
        $filtered = array_intersect_key($payload, $allowed);

        foreach ($filtered as $key => $value) {
            $this->optionService->set($key, $this->normalizeStoredValue($key, $value), 'yes');
        }

        return $this->readSection($section);
    }

    private function normalizeStoredValue(string $key, mixed $value): string
    {
        if (in_array($key, self::BOOLEAN_KEYS, true)) {
            return $this->isTruthy($value) ? '1' : '0';
        }

        if (in_array($key, self::INTEGER_KEYS, true)) {
            return (string) ((int) ($value ?? 0));
        }

        return (string) ($value ?? '');
    }

    private function castOutputValue(string $key, mixed $value): mixed
    {
        if (in_array($key, self::BOOLEAN_KEYS, true)) {
            return $this->isTruthy($value);
        }

        if (in_array($key, self::INTEGER_KEYS, true)) {
            return (int) ($value ?? 0);
        }

        return $value ?? '';
    }

    private function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
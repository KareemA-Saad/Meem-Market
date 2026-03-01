<?php

namespace App\Http\Controllers\Api\V1\Admin;

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
            'blogname',
            'blogdescription',
            'siteurl',
            'home',
            'admin_email',
            'users_can_register',
            'default_role',
            'timezone_string',
            'date_format',
            'time_format',
            'start_of_week',
        ],
        'writing' => [
            'default_category',
            'default_post_format',
        ],
        'reading' => [
            'show_on_front',
            'page_on_front',
            'page_for_posts',
            'posts_per_page',
            'blog_public',
        ],
        'discussion' => [
            'default_comment_status',
            'require_name_email',
            'comment_registration',
            'comment_moderation',
            'moderation_keys',
            'disallowed_keys',
            'comments_notify',
            'show_avatars',
            'avatar_default',
            'avatar_rating',
            'close_comments_days_old',
            'thread_comments',
            'thread_comments_depth',
            'page_comments',
            'comments_per_page',
            'default_comments_page',
            'comment_order',
        ],
        'media' => [
            'thumbnail_size_w',
            'thumbnail_size_h',
            'thumbnail_crop',
            'medium_size_w',
            'medium_size_h',
            'large_size_w',
            'large_size_h',
            'uploads_use_yearmonth_folders',
        ],
        'permalinks' => [
            'permalink_structure',
            'category_base',
            'tag_base',
        ],
        'privacy' => [
            'wp_page_for_privacy_policy',
        ],
    ];

    public function __construct(
        private readonly OptionService $optionService,
    ) {}

    // ─── GET Section ─────────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/admin/settings/{section}",
        operationId: "getSettings",
        summary: "Get settings for a section",
        description: "Returns all options for the specified section as key-value pairs.",
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
        $keys = self::SECTION_KEYS[$section] ?? null;

        if (!$keys) {
            return $this->error("Unknown settings section: {$section}", 404);
        }

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = $this->optionService->get($key);
        }

        return $this->success($settings);
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

        foreach ($data as $key => $value) {
            $this->optionService->set($key, $value);
        }

        // Return the full section after update
        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = $this->optionService->get($key);
        }

        return $this->success($settings);
    }
}

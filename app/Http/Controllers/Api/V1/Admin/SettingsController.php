<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\UpdateDiscussionSettingsRequest;
use App\Http\Requests\Admin\UpdateGeneralSettingsRequest;
use App\Http\Requests\Admin\UpdateMediaSettingsRequest;
use App\Http\Requests\Admin\UpdatePermalinkSettingsRequest;
use App\Http\Requests\Admin\UpdatePrivacySettingsRequest;
use App\Http\Requests\Admin\UpdateReadingSettingsRequest;
use App\Http\Requests\Admin\UpdateWritingSettingsRequest;
use App\Http\Resources\V1\Admin\SettingsResource;
use App\Services\OptionService;
use Illuminate\Http\JsonResponse;

class SettingsController extends ApiController
{
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
        'blogname' => 'MeemMark',
        'blogdescription' => 'Just another site',
        'siteurl' => 'http://localhost:8000',
        'home' => 'http://localhost:8000',
        'admin_email' => 'admin@meemmark.com',
        'users_can_register' => '0',
        'default_role' => 'subscriber',
        'timezone_string' => 'Asia/Riyadh',
        'date_format' => 'F j, Y',
        'time_format' => 'g:i a',
        'start_of_week' => '1',
        'default_category' => '1',
        'default_post_format' => '0',
        'show_on_front' => 'posts',
        'page_on_front' => '0',
        'page_for_posts' => '0',
        'posts_per_page' => '10',
        'blog_public' => '1',
        'default_comment_status' => 'open',
        'require_name_email' => '1',
        'comment_registration' => '0',
        'comment_moderation' => '0',
        'moderation_keys' => '',
        'disallowed_keys' => '',
        'comments_notify' => '1',
        'show_avatars' => '1',
        'avatar_default' => 'mystery',
        'avatar_rating' => 'g',
        'close_comments_days_old' => '0',
        'thread_comments' => '0',
        'thread_comments_depth' => '5',
        'page_comments' => '0',
        'comments_per_page' => '50',
        'default_comments_page' => 'newest',
        'comment_order' => 'asc',
        'thumbnail_size_w' => '150',
        'thumbnail_size_h' => '150',
        'thumbnail_crop' => '1',
        'medium_size_w' => '300',
        'medium_size_h' => '300',
        'large_size_w' => '1024',
        'large_size_h' => '1024',
        'uploads_use_yearmonth_folders' => '1',
        'permalink_structure' => '/%postname%/',
        'category_base' => '',
        'tag_base' => '',
        'wp_page_for_privacy_policy' => '0',
    ];

    private const BOOLEAN_KEYS = [
        'users_can_register', 'blog_public', 'require_name_email', 'comment_registration', 'comment_moderation',
        'comments_notify', 'show_avatars', 'thread_comments', 'page_comments', 'thumbnail_crop',
        'uploads_use_yearmonth_folders',
    ];

    private const INTEGER_KEYS = [
        'start_of_week', 'default_category', 'page_on_front', 'page_for_posts', 'posts_per_page',
        'close_comments_days_old', 'thread_comments_depth', 'comments_per_page', 'thumbnail_size_w',
        'thumbnail_size_h', 'medium_size_w', 'medium_size_h', 'large_size_w', 'large_size_h',
        'wp_page_for_privacy_policy',
    ];

    public function __construct(
        private readonly OptionService $optionService,
    ) {}

    public function general(): JsonResponse
    {
        return $this->success(new SettingsResource($this->readSection('general')));
    }

    public function updateGeneral(UpdateGeneralSettingsRequest $request): JsonResponse
    {
        return $this->success(new SettingsResource($this->writeSection('general', $request->validated())));
    }

    public function writing(): JsonResponse
    {
        return $this->success(new SettingsResource($this->readSection('writing')));
    }

    public function updateWriting(UpdateWritingSettingsRequest $request): JsonResponse
    {
        return $this->success(new SettingsResource($this->writeSection('writing', $request->validated())));
    }

    public function reading(): JsonResponse
    {
        return $this->success(new SettingsResource($this->readSection('reading')));
    }

    public function updateReading(UpdateReadingSettingsRequest $request): JsonResponse
    {
        return $this->success(new SettingsResource($this->writeSection('reading', $request->validated())));
    }

    public function discussion(): JsonResponse
    {
        return $this->success(new SettingsResource($this->readSection('discussion')));
    }

    public function updateDiscussion(UpdateDiscussionSettingsRequest $request): JsonResponse
    {
        return $this->success(new SettingsResource($this->writeSection('discussion', $request->validated())));
    }

    public function media(): JsonResponse
    {
        return $this->success(new SettingsResource($this->readSection('media')));
    }

    public function updateMedia(UpdateMediaSettingsRequest $request): JsonResponse
    {
        return $this->success(new SettingsResource($this->writeSection('media', $request->validated())));
    }

    public function permalinks(): JsonResponse
    {
        return $this->success(new SettingsResource($this->readSection('permalinks')));
    }

    public function updatePermalinks(UpdatePermalinkSettingsRequest $request): JsonResponse
    {
        return $this->success(new SettingsResource($this->writeSection('permalinks', $request->validated())));
    }

    public function privacy(): JsonResponse
    {
        return $this->success(new SettingsResource($this->readSection('privacy')));
    }

    public function updatePrivacy(UpdatePrivacySettingsRequest $request): JsonResponse
    {
        return $this->success(new SettingsResource($this->writeSection('privacy', $request->validated())));
    }

    /**
     * @return array<string, mixed>
     */
    private function readSection(string $section): array
    {
        $keys = self::SECTION_KEYS[$section] ?? [];
        $result = [];

        foreach ($keys as $key) {
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
        $allowed = array_flip(self::SECTION_KEYS[$section] ?? []);
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

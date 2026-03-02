<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateDiscussionSettingsRequest extends BaseSettingsRequest
{
    public function rules(): array
    {
        return [
            'default_comment_status' => ['sometimes', Rule::in(['open', 'closed'])],
            'require_name_email' => ['sometimes', 'boolean'],
            'comment_registration' => ['sometimes', 'boolean'],
            'comment_moderation' => ['sometimes', 'boolean'],
            'moderation_keys' => ['sometimes', 'nullable', 'string'],
            'disallowed_keys' => ['sometimes', 'nullable', 'string'],
            'comments_notify' => ['sometimes', 'boolean'],
            'show_avatars' => ['sometimes', 'boolean'],
            'avatar_default' => ['sometimes', 'nullable', 'string', 'max:100'],
            'avatar_rating' => ['sometimes', Rule::in(['g', 'pg', 'r', 'x'])],
            'close_comments_days_old' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:3650'],
            'thread_comments' => ['sometimes', 'boolean'],
            'thread_comments_depth' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10'],
            'page_comments' => ['sometimes', 'boolean'],
            'comments_per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:500'],
            'default_comments_page' => ['sometimes', Rule::in(['oldest', 'newest'])],
            'comment_order' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
}

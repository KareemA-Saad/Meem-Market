<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateReadingSettingsRequest extends BaseSettingsRequest
{
    public function rules(): array
    {
        return [
            'show_on_front' => ['sometimes', Rule::in(['posts', 'page'])],
            'page_on_front' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('posts', 'id')->where(fn ($q) => $q->where('type', 'page')),
            ],
            'page_for_posts' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('posts', 'id')->where(fn ($q) => $q->where('type', 'page')),
            ],
            'posts_per_page' => ['sometimes', 'integer', 'min:1', 'max:500'],
            'blog_public' => ['sometimes', 'boolean'],
        ];
    }
}

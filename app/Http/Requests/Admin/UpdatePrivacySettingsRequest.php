<?php

namespace App\Http\Requests\Admin;
use Illuminate\Validation\Rule;

class UpdatePrivacySettingsRequest extends BaseSettingsRequest
{
    public function rules(): array
    {
        return [
            'wp_page_for_privacy_policy' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('posts', 'id')->where(fn ($q) => $q->where('type', 'page')),
            ],
        ];
    }
}

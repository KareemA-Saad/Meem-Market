<?php

namespace App\Http\Requests\Admin;

class UpdatePermalinkSettingsRequest extends BaseSettingsRequest
{
    public function rules(): array
    {
        return [
            'permalink_structure' => ['sometimes', 'nullable', 'string', 'max:255'],
            'category_base' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tag_base' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}

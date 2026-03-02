<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateGeneralSettingsRequest extends BaseSettingsRequest
{
    public function rules(): array
    {
        return [
            'blogname' => ['sometimes', 'string', 'max:255'],
            'blogdescription' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'siteurl' => ['sometimes', 'url', 'max:255'],
            'home' => ['sometimes', 'url', 'max:255'],
            'admin_email' => ['sometimes', 'email', 'max:255'],
            'users_can_register' => ['sometimes', 'boolean'],
            'default_role' => ['sometimes', Rule::in(['administrator', 'editor', 'author', 'contributor', 'subscriber'])],
            'timezone_string' => ['sometimes', 'string', 'max:100'],
            'date_format' => ['sometimes', 'string', 'max:100'],
            'time_format' => ['sometimes', 'string', 'max:100'],
            'start_of_week' => ['sometimes', 'integer', 'min:0', 'max:6'],
        ];
    }
}

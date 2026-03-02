<?php

namespace App\Http\Requests\Admin;
use Illuminate\Validation\Rule;

class UpdateWritingSettingsRequest extends BaseSettingsRequest
{
    public function rules(): array
    {
        return [
            'default_category' => [
                'sometimes',
                'integer',
                Rule::exists('term_taxonomy', 'id')->where(fn ($q) => $q->where('taxonomy', 'category')),
            ],
            'default_post_format' => ['sometimes', 'nullable', 'string', 'max:50'],
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

class UpdateMediaSettingsRequest extends BaseSettingsRequest
{
    public function rules(): array
    {
        return [
            'thumbnail_size_w' => ['sometimes', 'integer', 'min:0', 'max:5000'],
            'thumbnail_size_h' => ['sometimes', 'integer', 'min:0', 'max:5000'],
            'thumbnail_crop' => ['sometimes', 'boolean'],
            'medium_size_w' => ['sometimes', 'integer', 'min:0', 'max:5000'],
            'medium_size_h' => ['sometimes', 'integer', 'min:0', 'max:5000'],
            'large_size_w' => ['sometimes', 'integer', 'min:0', 'max:10000'],
            'large_size_h' => ['sometimes', 'integer', 'min:0', 'max:10000'],
            'uploads_use_yearmonth_folders' => ['sometimes', 'boolean'],
        ];
    }
}

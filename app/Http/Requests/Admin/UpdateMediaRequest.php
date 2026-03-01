<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates media metadata updates (title, caption, alt text, description).
 */
class UpdateMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'caption' => ['sometimes', 'string', 'max:5000'],
            'alt_text' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:10000'],
        ];
    }
}

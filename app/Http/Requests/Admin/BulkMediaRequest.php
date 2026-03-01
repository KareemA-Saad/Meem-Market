<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates bulk media actions (currently: delete).
 */
class BulkMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:delete'],
            'media_ids' => ['required', 'array', 'min:1'],
            'media_ids.*' => ['integer', 'exists:posts,id'],
        ];
    }
}

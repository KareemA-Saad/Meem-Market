<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates menu item updates.
 */
class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'url' => ['sometimes', 'string', 'max:500'],
            'position' => ['sometimes', 'integer', 'min:0'],
            'parent_item_id' => ['sometimes', 'nullable', 'integer'],
            'target' => ['sometimes', 'nullable', 'string', 'in:_blank,_self'],
            'css_classes' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}

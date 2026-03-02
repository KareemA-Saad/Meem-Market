<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates menu item creation.
 */
class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:custom,post_type,taxonomy'],
            'object_id' => ['sometimes', 'nullable', 'integer'],
            'url' => ['sometimes', 'string', 'max:500'],
            'title' => ['required', 'string', 'max:255'],
            'parent_item_id' => ['sometimes', 'nullable', 'integer'],
            'position' => ['sometimes', 'integer', 'min:0'],
            'target' => ['sometimes', 'nullable', 'string', 'in:_blank,_self'],
            'css_classes' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}

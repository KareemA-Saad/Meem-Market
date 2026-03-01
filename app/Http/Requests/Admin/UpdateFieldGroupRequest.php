<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates ACF-style field group updates. Same shape as store, all optional.
 */
class UpdateFieldGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:publish,draft'],
            'position' => ['sometimes', 'string', 'in:normal,side,acf_after_title'],
            'style' => ['sometimes', 'string', 'in:default,seamless'],
            'label_placement' => ['sometimes', 'string', 'in:top,left'],
            'menu_order' => ['sometimes', 'integer', 'min:0'],

            'fields' => ['sometimes', 'array'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.name' => ['required', 'string', 'max:200', 'regex:/^[a-z0-9_]+$/'],
            'fields.*.type' => ['required', 'string'],
            'fields.*.instructions' => ['sometimes', 'string'],
            'fields.*.required' => ['sometimes', 'boolean'],
            'fields.*.default_value' => ['sometimes', 'nullable', 'string'],
            'fields.*.options' => ['sometimes', 'array'],

            'location_rules' => ['sometimes', 'array'],
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates custom post type / taxonomy definition updates. All fields optional.
 */
class UpdateContentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'string', 'max:255'],
            'singular_label' => ['sometimes', 'string', 'max:255'],
            'labels' => ['sometimes', 'array'],
            'public' => ['sometimes', 'boolean'],
            'show_ui' => ['sometimes', 'boolean'],
            'has_archive' => ['sometimes', 'boolean'],
            'hierarchical' => ['sometimes', 'boolean'],
            'supports' => ['sometimes', 'array'],
            'supports.*' => ['string'],
            'taxonomies' => ['sometimes', 'array'],
            'taxonomies.*' => ['string'],
            'menu_icon' => ['sometimes', 'nullable', 'string', 'max:255'],
            'menu_position' => ['sometimes', 'nullable', 'integer'],
        ];
    }
}

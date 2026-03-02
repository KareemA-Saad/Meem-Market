<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates custom post type / taxonomy definition creation.
 */
class StoreContentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:20', 'regex:/^[a-z0-9_\-]+$/'],
            'label' => ['required', 'string', 'max:255'],
            'singular_label' => ['sometimes', 'string', 'max:255'],
            'labels' => ['sometimes', 'array'],
            'public' => ['sometimes', 'boolean'],
            'show_ui' => ['sometimes', 'boolean'],
            'has_archive' => ['sometimes', 'boolean'],
            'hierarchical' => ['sometimes', 'boolean'],
            'supports' => ['sometimes', 'array'],
            'supports.*' => ['string', 'in:title,editor,author,thumbnail,excerpt,trackbacks,custom-fields,comments,revisions,page-attributes,post-formats'],
            'taxonomies' => ['sometimes', 'array'],
            'taxonomies.*' => ['string'],
            'menu_icon' => ['sometimes', 'nullable', 'string', 'max:255'],
            'menu_position' => ['sometimes', 'nullable', 'integer'],
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates post/page updates — all fields optional.
 */
class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'excerpt' => ['sometimes', 'string', 'max:5000'],
            'status' => ['sometimes', 'string', 'in:publish,draft,pending,private,future'],
            'slug' => ['sometimes', 'string', 'max:200'],
            'password' => ['sometimes', 'nullable', 'string', 'max:255'],
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['integer', 'exists:term_taxonomy,id'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['integer', 'exists:term_taxonomy,id'],
            'featured_image_id' => ['sometimes', 'nullable', 'integer'],
            'menu_order' => ['sometimes', 'integer', 'min:0'],
            'author_id' => ['sometimes', 'integer', 'exists:users,id'],
            'scheduled_at' => ['sometimes', 'nullable', 'date', 'after:now'],
            // Page-specific
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:posts,id'],
            'template' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}

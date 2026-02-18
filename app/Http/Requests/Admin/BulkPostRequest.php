<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates bulk post/page actions.
 */
class BulkPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:trash,restore,delete,edit'],
            'post_ids' => ['required', 'array', 'min:1'],
            'post_ids.*' => ['integer', 'exists:posts,id'],
            'data' => ['required_if:action,edit', 'array'],
            'data.status' => ['sometimes', 'string', 'in:publish,draft,pending,private'],
            'data.category' => ['sometimes', 'integer', 'exists:term_taxonomy,id'],
            'data.tag' => ['sometimes', 'integer', 'exists:term_taxonomy,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'data.required_if' => 'The data field is required when using the edit action.',
        ];
    }
}

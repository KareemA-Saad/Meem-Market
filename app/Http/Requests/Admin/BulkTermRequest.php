<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['delete'])],
            'term_ids' => 'required|array|min:1',
            'term_ids.*' => 'integer|exists:term_taxonomy,id',
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'The action field is required.',
            'action.in' => 'Invalid bulk action.',
            'term_ids.required' => 'At least one term must be selected.',
            'term_ids.*.exists' => 'One or more selected terms do not exist.',
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:200',
            'slug' => 'nullable|string|max:200|regex:/^[a-z0-9-]+$/',
            'parent_id' => 'nullable|integer|exists:term_taxonomy,id',
            'description' => 'nullable|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'slug.regex' => 'The slug may only contain lowercase letters, numbers, and hyphens.',
            'parent_id.exists' => 'The selected parent term does not exist.',
        ];
    }
}

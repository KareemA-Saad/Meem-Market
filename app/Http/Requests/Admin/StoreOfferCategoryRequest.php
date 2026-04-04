<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOfferCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id'   => ['required', 'integer', 'exists:branches,id'],
            'title'       => ['required', 'string', 'max:255'],
            'slug'        => ['sometimes', 'string', 'max:255'],
            'cover_image' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active'   => ['sometimes', 'boolean'],
            'sort_order'  => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.exists'          => 'The selected branch does not exist.',
            'cover_image.mimes'         => 'The cover image must be a jpeg, png, webp, or gif file.',
            'cover_image.max'           => 'The cover image must not exceed 5MB.',
            'end_date.after_or_equal'   => 'The end date must be on or after the start date.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'code'    => 'VALIDATION_ERROR',
            'errors'  => $validator->errors(),
        ], 422));
    }
}

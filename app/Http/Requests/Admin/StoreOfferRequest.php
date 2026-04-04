<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'offer_category_id' => ['required', 'integer', 'exists:offer_categories,id'],
            'title'             => ['nullable', 'string', 'max:255'],
            'image'             => ['required', 'file', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'is_active'         => ['sometimes', 'boolean'],
            'sort_order'        => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'offer_category_id.exists' => 'The selected offer category does not exist.',
            'image.required'           => 'An image file is required.',
            'image.mimes'              => 'The image must be a jpeg, png, webp, or gif file.',
            'image.max'                => 'The image must not exceed 5MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => $this->normalizeBoolean($this->input('is_active')),
            ]);
        }
    }

    private function normalizeBoolean(mixed $value): mixed
    {
        if (is_bool($value) || $value === null) {
            return $value;
        }

        if (is_int($value)) {
            return match ($value) {
                1 => true,
                0 => false,
                default => $value,
            };
        }

        if (is_string($value)) {
            return match (strtolower(trim($value))) {
                '1', 'true', 'yes', 'on' => true,
                '0', 'false', 'no', 'off' => false,
                default => $value,
            };
        }

        return $value;
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

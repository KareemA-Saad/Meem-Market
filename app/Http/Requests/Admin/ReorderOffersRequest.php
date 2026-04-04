<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReorderOffersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items'              => ['required', 'array', 'min:1'],
            'items.*.id'         => ['required', 'integer', 'exists:offers,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'              => 'At least one item is required for reordering.',
            'items.*.id.exists'           => 'One or more offer IDs are invalid.',
            'items.*.sort_order.required' => 'Each item must have a sort_order value.',
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

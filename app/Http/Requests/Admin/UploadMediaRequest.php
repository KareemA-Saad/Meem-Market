<?php

namespace App\Http\Requests\Admin;

use App\Services\MediaService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Validates media file uploads.
 * Accepts one or more files with extension/size constraints.
 */
class UploadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $extensions = implode(',', MediaService::allowedExtensions());

        return [
            'files'       => ['required', 'array', 'min:1', 'max:20'],
            'files.*'     => ['required', 'file', "mimes:{$extensions}", 'max:51200'], // 50 MB per file
            'attached_to' => ['sometimes', 'nullable', 'integer', 'exists:posts,id'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'code' => 'VALIDATION_ERROR',
            'errors' => $validator->errors(),
        ], 422));
    }
}

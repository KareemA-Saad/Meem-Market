<?php

namespace App\Http\Requests\Admin;

use App\Services\MediaService;
use Illuminate\Foundation\Http\FormRequest;

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
}
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates comment content/metadata updates during moderation.
 */
class UpdateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'author_name' => ['sometimes', 'string', 'max:255'],
            'author_email' => ['sometimes', 'email', 'max:100'],
            'author_url' => ['sometimes', 'string', 'max:200'],
            'content' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string', 'in:0,1,spam,trash'],
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates bulk comment moderation actions.
 */
class BulkCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:approve,unapprove,spam,trash,delete'],
            'comment_ids' => ['required', 'array', 'min:1'],
            'comment_ids.*' => ['integer', 'exists:comments,id'],
        ];
    }
}

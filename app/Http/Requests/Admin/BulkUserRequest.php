<?php

namespace App\Http\Requests\Admin;

use App\Services\RoleService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class BulkUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:delete,change_role'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'role' => ['required_if:action,change_role', 'string', Rule::in($this->availableRoles())],
            'reassign_to' => ['sometimes', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.in' => 'Action must be either "delete" or "change_role".',
            'user_ids.required' => 'At least one user must be selected.',
            'role.required_if' => 'A role is required when changing roles.',
            'role.in' => 'The selected role is invalid.',
        ];
    }

    /**
     * @return list<string>
     */
    private function availableRoles(): array
    {
        $roles = app(RoleService::class)->getRoles();

        return array_keys($roles);
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

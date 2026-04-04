<?php

namespace App\Http\Requests\Admin;

use App\Services\RoleService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user') ?? $this->route('id');

        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'nickname' => ['sometimes', 'string', 'max:255'],
            'display_name' => ['sometimes', 'string', 'max:250'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($userId)],
            'url' => ['sometimes', 'string', 'max:100'],
            'bio' => ['sometimes', 'string', 'max:5000'],
            'password' => ['sometimes', 'string', 'min:8'],
            'role' => ['sometimes', 'string', Rule::in($this->availableRoles())],
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

<?php

namespace App\Http\Requests\Admin;

use App\Services\RoleService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'min:3', 'max:60', 'unique:users,login'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['sometimes', 'string', 'min:8'],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'url' => ['sometimes', 'string', 'max:100'],
            'role' => ['required', 'string', Rule::in($this->availableRoles())],
            'send_notification' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'login.unique' => 'This username is already registered.',
            'email.unique' => 'This email address is already registered.',
            'role.required' => 'A role must be assigned to the user.',
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

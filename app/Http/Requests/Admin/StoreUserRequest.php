<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

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
            'role' => ['required', 'string'],
            'send_notification' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'login.unique' => 'This username is already registered.',
            'email.unique' => 'This email address is already registered.',
            'role.required' => 'A role must be assigned to the user.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $validator->errors(),
        ], 422));
    }
}

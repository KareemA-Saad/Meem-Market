<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EditMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['crop', 'rotate', 'flip', 'scale'])],
            'params' => ['required', 'array'],
            'params.x' => ['sometimes', 'numeric', 'min:0'],
            'params.y' => ['sometimes', 'numeric', 'min:0'],
            'params.width' => ['sometimes', 'numeric', 'min:1'],
            'params.height' => ['sometimes', 'numeric', 'min:1'],
            'params.angle' => ['sometimes', 'numeric'],
            'params.mode' => ['sometimes', Rule::in(['horizontal', 'vertical'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $action = $this->input('action');
            $params = $this->input('params', []);

            if ($action === 'crop') {
                foreach (['x', 'y', 'width', 'height'] as $key) {
                    if (!array_key_exists($key, $params)) {
                        $validator->errors()->add("params.{$key}", "The params.{$key} field is required for crop.");
                    }
                }
            }

            if ($action === 'rotate' && !array_key_exists('angle', $params)) {
                $validator->errors()->add('params.angle', 'The params.angle field is required for rotate.');
            }

            if ($action === 'flip' && !array_key_exists('mode', $params)) {
                $validator->errors()->add('params.mode', 'The params.mode field is required for flip.');
            }

            if ($action === 'scale' && !array_key_exists('width', $params) && !array_key_exists('height', $params)) {
                $validator->errors()->add('params', 'Scale requires params.width and/or params.height.');
            }
        });
    }
}

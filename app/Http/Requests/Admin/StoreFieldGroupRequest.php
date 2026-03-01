<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates ACF-style field group creation with nested fields and location rules.
 */
class StoreFieldGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:publish,draft'],
            'position' => ['sometimes', 'string', 'in:normal,side,acf_after_title'],
            'style' => ['sometimes', 'string', 'in:default,seamless'],
            'label_placement' => ['sometimes', 'string', 'in:top,left'],
            'menu_order' => ['sometimes', 'integer', 'min:0'],

            // Fields array
            'fields' => ['sometimes', 'array'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.name' => ['required', 'string', 'max:200', 'regex:/^[a-z0-9_]+$/'],
            'fields.*.type' => ['required', 'string', 'in:text,textarea,number,email,url,select,checkbox,radio,image,file,wysiwyg,date_picker,true_false,repeater,group'],
            'fields.*.instructions' => ['sometimes', 'string'],
            'fields.*.required' => ['sometimes', 'boolean'],
            'fields.*.default_value' => ['sometimes', 'nullable', 'string'],
            'fields.*.options' => ['sometimes', 'array'],

            // Location rules
            'location_rules' => ['sometimes', 'array'],
            'location_rules.*' => ['array'],
            'location_rules.*.*.param' => ['required', 'string'],
            'location_rules.*.*.operator' => ['required', 'string', 'in:==,!='],
            'location_rules.*.*.value' => ['required', 'string'],
        ];
    }
}

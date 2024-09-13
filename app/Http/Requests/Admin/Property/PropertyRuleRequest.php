<?php

namespace App\Http\Requests\Admin\Property;

use Illuminate\Foundation\Http\FormRequest;

class PropertyRuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $modelId = $this->property_rule ?: null;

        $uniqueNameRule = ($this->method() === 'PUT' && $modelId !== null)
            ? 'unique:property_rules,rule_title,' . $modelId
            : 'unique:property_rules,rule_title';

        return [
            'rule_title'  => "required|string|max:255|{$uniqueNameRule}",
            'rule_note' => 'required|string',
        ];
    }
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */

    public function messages(): array
    {
        return [
            'rule_title.required' => 'The rule title field is required.',
            'rule_title.string'   => 'The rule title field must be a string.',
            'rule_title.max'      => 'The rule title field must not exceed 255 characters.',
            'rule_title.unique'   => 'The rule title field must be unique.',
            'rule_note.required' => 'The notes field is required.',
            'rule_note.string'  => 'The notes field must be a string.',
        ];
    }
}

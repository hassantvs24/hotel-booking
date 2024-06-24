<?php

namespace App\Http\Requests\Admin\Property;

use Illuminate\Foundation\Http\FormRequest;

class PropertyCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize() : bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules() : array
    {
        $modelId = $this->propertyCategory ?: null;

        $uniqueNameRule = ($this->method() === 'PUT' && $modelId !== null)
            ? 'unique:property_categories,name,' . $modelId
            : 'unique:property_categories,name';

        return [
            'name'  => "required|string|max:255|{$uniqueNameRule}",
            'notes' => 'nullable|string',
            'icon'  => 'nullable'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */

    public function messages() : array
    {
        return [
            'name.required' => 'The name field is required.',
            'name.string'   => 'The name field must be a string.',
            'name.max'      => 'The name field must not exceed 255 characters.',
            'name.unique'   => 'The name field must be unique.',
            'notes.string'  => 'The notes field must be a string.',
        ];
    }
}

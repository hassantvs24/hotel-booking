<?php

namespace App\Http\Requests\Admin\Room;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PriceTypeRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules() : array
    {
        $modelId = $this->priceType ?: null;

        $uniqueNameRule = ($this->method() === 'PUT' && $modelId !== null)
            ? 'unique:price_types,name,' . $modelId
            : 'unique:price_types,name';

        return [
            'name' => "required|string|max:255|{$uniqueNameRule}"
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

        ];
    }
}

<?php

namespace App\Http\Requests\Admin\Place;

use Illuminate\Foundation\Http\FormRequest;

class StateRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            'short_name' => 'required|string|max:30',
            'country_id' => 'required|integer|exists:countries,id',
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
            'name.required' => 'State name is required',
            'name.string' => 'State name must be a string',
            'name.max' => 'State name must not be greater than 255 characters',
            'short_name.max' => 'Short name must not be greater than 30 characters',
            'country_id.required' => 'Country is required',
        ];
    }
}

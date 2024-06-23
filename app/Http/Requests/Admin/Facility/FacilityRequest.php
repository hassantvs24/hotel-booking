<?php

namespace App\Http\Requests\Admin\Facility;

use Illuminate\Foundation\Http\FormRequest;

class FacilityRequest extends FormRequest
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
        $modelId = $this->facility?: null;

        $uniqueNameRule = ($this->method() === 'PUT' && $modelId !== null)
            ? 'unique:facilities,name,' . $modelId
            : 'unique:facilities,name';

        return [
            'name'      => "required|string|max:255|{$uniqueNameRule}",
            'facility_type'  => 'required',
            'notes' => 'nullable|string'
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
            'name.required'         => 'The  name is required.',
            'name.unique'           => 'The name has already been taken.',
            'name.string'       => 'The name must be an string.',
            'facility_type.required' => 'Facility type is required',
            'notes.string'       => 'The note must be an string.',
        ];
    }
}

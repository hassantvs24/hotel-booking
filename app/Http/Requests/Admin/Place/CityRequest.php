<?php

namespace App\Http\Requests\Admin\Place;

use Illuminate\Foundation\Http\FormRequest;

class CityRequest extends FormRequest
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
        $modelId = $this->city ?: null;

        $uniqueNameRule = ($this->method() === 'PUT' && $modelId !== null)
            ? 'unique:cities,name,' . $modelId
            : 'unique:cities,name';

        return [
            'name'      => "required|string|max:255|{$uniqueNameRule}",
            'zip_code'  => 'required|string|max:255',
            'state_id' => 'required|integer|exists:states,id',
            'lat'       => 'required|numeric',
            'long'      => 'required|numeric',
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
            'name.required'     => 'City name is required',
            'zip_code.required' => 'Zip code is required',
            'state_id.required' => 'State is required',
            'lat.required'      => 'Latitude is required',
            'lat.numeric'       => 'Latitude must be a number',
            'long.required'     => 'Longitude is required',
            'long.numeric'      => 'Longitude must be a number',
        ];
    }
}

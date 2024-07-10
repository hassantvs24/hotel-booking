<?php

namespace App\Http\Requests\Admin\Place;

use Illuminate\Foundation\Http\FormRequest;

class PlaceRequest extends FormRequest
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
        $modelId = $this->route('place');

        $uniqueNameRule = $modelId ? 'unique:places,name,' . $modelId : 'unique:places,name';

        return [
            'name'              => "required|string|max:255|{$uniqueNameRule}",
            'lat'               => 'required|numeric',
            'long'              => 'required|numeric',
            'zip_code'          => 'required|string|max:255',
            'description'       => 'nullable|string',
            'nearest_police'    => 'required|string|max:255',
            'nearest_hospital'  => 'required|string|max:255',
            'nearest_fire'      => 'required|string|max:255',
            'city_id'           => 'required|integer|exists:cities,id',
            'photo'  => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
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
            'name.required'             => 'The place name is required.',
            'name.unique'               => 'The place name has already been taken.',
            'name.max'                  => 'The place name cannot be longer than 255 characters.',
            'lat.required'              => 'The latitude is required.',
            'lat.numeric'               => 'The latitude must be a number.',
            'long.required'             => 'The longitude is required.',
            'long.numeric'              => 'The longitude must be a number.',
            'zip_code.required'         => 'The zip code is required.',
            'zip_code.max'              => 'The zip code cannot be longer than 255 characters.',
            'nearest_police.required'   => 'The nearest police is required.',
            'nearest_police.string'     => 'The nearest police must be a string.',
            'nearest_police.max'        => 'The nearest police cannot be longer than 255 characters.',
            'nearest_hospital.required' => 'The nearest hospital is required.',
            'nearest_hospital.string'   => 'The nearest hospital must be a string.',
            'nearest_hospital.max'      => 'The nearest hospital cannot be longer than 255 characters.',
            'nearest_fire.required'     => 'The nearest fire station is required.',
            'nearest_fire.string'       => 'The nearest fire station must be a string.',
            'nearest_fire.max'          => 'The nearest fire station cannot be longer than 255 characters.',
            'city_id.required'          => 'The city is required.',
            'city_id.integer'           => 'The city ID must be an integer.',
            'city_id.exists'            => 'The selected city is invalid.',
            'photo.image'               => 'The photo must be an image.',
            'photo.max'                 => 'The photo must not be larger than 2MB.',
            'photo.mimes'               => 'The photo must be a jpeg,png,jpg,gif,svg file.',
        ];
    }
}

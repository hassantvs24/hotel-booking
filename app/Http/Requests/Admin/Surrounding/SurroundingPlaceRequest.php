<?php

namespace App\Http\Requests\Admin\Surrounding;

use Illuminate\Foundation\Http\FormRequest;

class SurroundingPlaceRequest extends FormRequest
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
        $modelId = $this->surrounding_place ?: null;

        $uniqueNameRule = ($this->method() === 'PUT' && $modelId !== null)
            ? 'unique:surrounding_places,name,' . $modelId
            : 'unique:surrounding_places,name';

        return [
            'name'           => "required|string|max:255|{$uniqueNameRule}",
            'lat'            => 'required|numeric',
            'long'           => 'required|numeric',
            'notes'          => 'nullable|string',
            'photo'          => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'place_id'       => 'required|integer|exists:places,id',
            'surrounding_id' => 'required|integer|exists:surroundings,id',
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
            'name.required'           => 'The  name is required.',
            'name.unique'             => 'The name has already been taken.',
            'name.string'             => 'The name must be an string.',
            'lat.required'            => 'The latitude is required.',
            'lat.numeric'             => 'The latitude must be a number.',
            'long.required'           => 'The longitude is required.',
            'long.numeric'            => 'The longitude must be a number.',
            'notes.string'            => 'The note must be an string.',
            'photo.image'             => 'The photo must be an image.',
            'photo.mimes'             => 'The photo must be a file of type: jpeg, png, jpg, gif, svg.',
            'photo.max'               => 'The photo may not be greater than 2048 kilobytes.',
            'place_id.required'       => 'The place is required.',
            'place_id.integer'        => 'The place ID must be an integer.',
            'place_id.exists'         => 'The selected place is invalid.',
            'surrounding_id.required' => 'The surrounding is required.',
            'surrounding_id.integer'  => 'The citsurroundingy ID must be an integer.',
            'surrounding_id.exists'   => 'The selected surrounding is invalid.',
        ];
    }
}

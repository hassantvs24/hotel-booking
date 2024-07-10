<?php

namespace App\Http\Requests\Admin\Room;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RoomRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $modelId = $this->room ?: null;

        $uniqueNameRule = ($this->method() === 'PUT' && $modelId !== null)
            ? 'unique:rooms,name,' . $modelId
            : 'unique:rooms,name';

        return [
            'name'           => "required|string|max:255|{$uniqueNameRule}",
            'photo'          => 'nullable',
            'room_number'    => 'nullable|integer',
            'room_size'      => 'nullable|integer',
            'guest_capacity' => 'nullable|integer',
            'extra_bed'      => 'nullable|boolean',
            'total_balcony'  => 'required|integer',
            'total_window'   => 'required',
            'base_price'     => 'required|integer',
            'notes'          => 'nullable|string',
            'bed_type_id'    => 'nullable|integer|exists:bed_types,id',
            'room_type_id'   => 'nullable|integer|exists:room_types,id',
            'property_id'    => 'nullable|integer|exists:properties,id',

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
            'name.required'          => 'The name field is required.',
            'name.string'            => 'The name must be a string.',
            'name.max'               => 'The name may not be greater than 255 characters.',
            'photo.nullable'         => 'The photo field is optional.',
            'room_number.integer'    => 'The room number must be an integer.',
            'room_size.integer'      => 'The room size must be an integer.',
            'guest_capacity.integer' => 'The guest capacity must be an integer.',
            'total_balcony.required' => 'The total balcony field is required.',
            'total_balcony.integer'  => 'The total balcony must be an integer.',
            'total_window.required'  => 'The total window field is required.',
            'base_price.required'    => 'The base price field is required.',
            'base_price.integer'     => 'The base price must be a number.',
            'notes.string'           => 'The notes must be a string.',
            'bed_type_id.integer'    => 'The bed type ID must be an integer.',
            'bed_type_id.exists'     => 'The selected bed type ID is invalid.',
            'room_type_id.integer'   => 'The room type ID must be an integer.',
            'room_type_id.exists'    => 'The selected room type ID is invalid.',
            'property_id.required'   => 'The property ID field is required.',
            'property_id.integer'    => 'The property ID must be an integer.',
            'property_id.exists'     => 'The selected property ID is invalid.',
        ];
    }
}

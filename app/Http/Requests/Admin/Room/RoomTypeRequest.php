<?php

namespace App\Http\Requests\Admin\Room;

use Illuminate\Foundation\Http\FormRequest;

class RoomTypeRequest extends FormRequest
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
        $modelId = $this->roomType ?: null;

        $uniqueNameRule = ($this->method() === 'PUT' && $modelId !== null)
            ? 'unique:room_types,name,' . $modelId
            : 'unique:room_types,name';

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

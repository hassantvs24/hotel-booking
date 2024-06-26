<?php

namespace App\Http\Requests\Admin\Room;

use Illuminate\Foundation\Http\FormRequest;

class BedTypeRequest extends FormRequest
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
        $modelId = $this->bedType ?: null;

        $uniqueNameRule = ($this->method() === 'PUT' && $modelId !== null)
            ? 'unique:bed_types,name,' . $modelId
            : 'unique:bed_types,name';

        return [
            'name'      => "required|string|max:255|{$uniqueNameRule}",
            'capacity'  => 'required|integer',
            'total_bed'  => 'required|integer',
            'bed_size'  => 'required|integer',
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
            'name.required'     => 'name is required',
            'name.string'       => 'name must be a string',
            'capacity.required' => 'capacity is required',
            'capacity.integer'  => 'capacity must be a number',
            'total_bed.required' => 'Total bed is required',
            'total_bed.integer' => 'capacity must be a number',
            'bed_size.required' => 'Bed size is required',
            'bed_size.integer'  => 'capacity must be a number',
        ];
    }
}

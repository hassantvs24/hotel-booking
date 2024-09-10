<?php

namespace App\Http\Requests\Portal;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'check_in' => 'required|date',
            'check_out' => 'required|date',
            'adult' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'room' => 'required|integer|min:1',
            'discount_price' => 'nullable|numeric|min:0',
            'room_id' => 'nullable|exists:rooms,id',
            'property_id' => 'nullable|exists:properties,id',
            'user_id' => 'nullable|exists:users,id',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'check_in.required' => 'The check-in date is required.',
            'check_in.date' => 'The check-in must be a valid date.',

            'check_out.required' => 'The check-out date is required.',
            'check_out.date' => 'The check-out must be a valid date.',

            'adult.required' => 'The number of adults is required.',
            'adult.integer' => 'The number of adults must be an integer.',
            'adult.min' => 'There must be at least one adult.',

            'children.integer' => 'The number of children must be an integer.',
            'children.min' => 'The number of children cannot be negative.',

            'room.required' => 'The number of rooms is required.',
            'room.integer' => 'The number of rooms must be an integer.',
            'room.min' => 'There must be at least one room.',

            'discount_price.numeric' => 'The discount price must be a number.',
            'discount_price.min' => 'The discount price cannot be negative.',

            'room_id.exists' => 'The selected room does not exist.',
            'property_id.exists' => 'The selected property does not exist.',
            'user_id.exists' => 'The selected user does not exist.',
        ];
    }
}

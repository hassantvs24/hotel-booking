<?php

namespace App\Http\Requests\Portal\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class VerdorRequest extends FormRequest
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
            'address' => 'required|string|max:255',
            'zip_code' => 'required|string|max:10',
            'total_room' => 'required|integer|min:1',
            'currency' => 'required|string|max:10',
            'phone_number' => 'required|string|max:15',
            'user_id' => 'required|integer|exists:users,id',

        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'The property name is required.',
            'address.required' => 'The address is required.',
            'zip_code.required' => 'The zip code is required.',
            'total_room.required' => 'The total number of rooms is required.',
            'currency.required' => 'The currency is required.',
            'phone_number.required' => 'The phone number is required.',
            'user_id.required' => 'The user ID is required.',
        ];
    }
}

<?php

namespace App\Http\Requests\Admin\Booking;

use Illuminate\Foundation\Http\FormRequest;

class BookingRequest extends FormRequest
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
            'booking_id'           => "required|numeric|max:255|",
            'adult'                =>  "required|numeric|between:-180,180",
            'check_in_time'        => 'nullable',
            'check_out_time'       => 'nullable',
            'notes'                => 'nullable|string',
            'children'             => "required|numeric|between:-180,180",
            'room'                 => 'nullable|string',
            'reference'            => 'nullable|string',
            'room_id'              => 'nullable|exists:room,id',
            'place_id'             => 'nullable|exists:places,id',
        ];
    }
}

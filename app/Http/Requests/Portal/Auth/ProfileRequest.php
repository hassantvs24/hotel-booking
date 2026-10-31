<?php

namespace App\Http\Requests\Portal\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * One part of the profile at a time: the profile page lets a guest change a single row (the name, the phone, the NID...),
 * so every field is checked only when it is sent. A field that is sent must be right; one that is not sent stays as it is.
 */
class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['nid' => 'nid', 'passport' => 'passport'] as $field => $type) {
            if ($this->has($field) && is_string($this->input($field))) {
                $this->merge([$field => \App\Services\Booking\GuestIdentity::normalize($this->input($field), $type) ?: null]);
            }
        }

        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => trim($this->input('phone'))]);
        }

        if (is_string($this->input('gender'))) {
            $this->merge(['gender' => strtolower(trim($this->input('gender'))) ?: null]);
        }
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Please enter your name.',
            'phone.required' => 'Please enter your phone number.',
            'phone.regex' => 'Enter a phone number with digits only, for example 01712 345678 (a + at the start is fine).',
            'nid.regex' => 'A NID number has 10, 13 or 17 digits.',
            'passport.regex' => 'A passport number has 6 to 20 letters and digits.',
            'date_of_birth.before' => 'The date of birth must be in the past.',
            'gender.in' => 'Choose male, female or other.',
        ];
    }

    public function rules(): array
    {
        return [
            'first_name'    => 'sometimes|required|string|max:255',
            'last_name'     => 'sometimes|nullable|string|max:255',
            'phone'         => ['sometimes', 'required', 'string', 'max:30', 'regex:/^\+?[0-9][0-9 \-]{5,19}$/'],
            'nid'           => ['sometimes', 'nullable', 'regex:/^(\d{10}|\d{13}|\d{17})$/'],
            'passport'      => ['sometimes', 'nullable', 'regex:/^[A-Za-z0-9]{6,20}$/'],
            'date_of_birth' => 'sometimes|nullable|date|before:today',
            'gender'        => ['sometimes', 'nullable', Rule::in(['male', 'female', 'other'])],
            'address'       => 'sometimes|nullable|string|max:255',
            'city'          => 'sometimes|nullable|string|max:255',
            'state'         => 'sometimes|nullable|string|max:255',
            'zip_code'      => 'sometimes|nullable|string|max:20',
            'country'       => 'sometimes|nullable|string|max:255',
        ];
    }
}

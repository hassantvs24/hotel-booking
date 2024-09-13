<?php

namespace App\Http\Requests\Portal\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
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
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => 'required|email|max:255',
            'phone'         => 'required|string|max:255',
            'nid'           => 'nullable|string|max:255',
            'passport'      => 'nullable|string|max:255',
            'date_of_birth' => 'required|date',
            'gender'        => 'required|string',
            'address'       => 'required|string',
            'city'          => 'nullable|string',
            'state'         => 'nullable|string',
            'zip_code'      => 'nullable|string',
            'country'       => 'nullable|string',
        ];
    }
}

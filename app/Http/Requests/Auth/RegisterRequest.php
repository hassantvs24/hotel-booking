<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'name'             => 'required|string',
            'email'            => 'required|email|unique:users,email',
            'password'         => 'required|string|min:6',
            'confirm_password' => 'required|same:password',
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
            'name.required'             => 'Name is required',
            'email.required'            => 'Email is required',
            'email.email'               => 'Email is invalid',
            'email.unique'              => 'Email is already taken',
            'password.required'         => 'Password is required',
            'password.min'              => 'Password must be at least 6 characters',
            'confirm_password.required' => 'Confirm password is required',
            'confirm_password.same'     => 'Password and Confirm password must match',
        ];
    }
}

<?php

namespace App\Http\Requests\Admin\ACL;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
        $modelId = $this->user?->id ?: null;

        $uniqueEmailRule = ($this->method() === 'PUT' && $modelId !== null)
            ? 'unique:users,email,' . $modelId
            : 'unique:users,email';

        $passwordRule = $this->method() === 'PUT' ? 'nullable' : 'required';

        return [
            'name'     => 'required|string|max:255',
            'email'    => "required|string|email|max:255|{$uniqueEmailRule}",
            'phone'    => 'nullable|string|max:255',
            'password' => "{$passwordRule}|string|min:8|max:255",
            'roles'    => 'nullable|array'
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
            'name.required'     => 'User name is required',
            'name.string'       => 'User name must be a string',
            'name.max'          => 'User name must not be greater than 255 characters',
            'email.required'    => 'User email is required',
            'email.string'      => 'User email must be a string',
            'email.email'       => 'User email must be a valid email',
            'email.max'         => 'User email must not be greater than 255 characters',
            'email.unique'      => 'User email already exists',
            'password.required' => 'User password is required',
            'password.string'   => 'User password must be a string',
            'password.min'      => 'User password must be at least 8 characters',
            'password.max'      => 'User password must not be greater than 255 characters',
            'phone.string'      => 'User phone must be a string',
            'phone.max'         => 'User phone must not be greater than 255 characters',
            'roles.array'       => 'User roles must be an array'
        ];
    }
}

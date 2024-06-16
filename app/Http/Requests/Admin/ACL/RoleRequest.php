<?php

namespace App\Http\Requests\Admin\ACL;

use Illuminate\Foundation\Http\FormRequest;

class RoleRequest extends FormRequest
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
        $modelId = $this->role ?: null;

        $uniqueRoleRule = ($this->method() === 'PUT' && $modelId !== null)
            ? 'unique:roles,name,' . $modelId
            : 'unique:roles,name';

        return [
            'name'        => "required|string|max:25|{$uniqueRoleRule}",
            'description' => 'nullable|string',
            'permissions' => 'nullable|array'
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
            'name.required'     => 'Role name is required',
            'name.string'       => 'Role name must be a string',
            'name.max'          => 'Role name must not be greater than 25 characters',
            'name.unique'       => 'Role name already exists',
            'description.string' => 'Role description must be a string',
            'permissions.array' => 'Permissions must be an array'
        ];
    }
}

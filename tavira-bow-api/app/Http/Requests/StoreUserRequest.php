<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'max:50',
                'unique:users,username',
                'regex:/^[a-zA-Z0-9_]+$/',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                Password::min(8)->mixedCase()->numbers(),
            ],
            'full_name' => [
                'required',
                'string',
                'max:100',
            ],
            'role' => [
                'sometimes',
                new Enum(UserRole::class),
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
            'primary_department' => [
                'nullable',
                'string',
                'max:100',
            ],
            'department_permissions' => [
                'sometimes',
                'array',
            ],
            'department_permissions.*.department' => [
                'required',
                'string',
            ],
            'department_permissions.*.can_view' => [
                'sometimes',
                'boolean',
            ],
            'department_permissions.*.can_edit_status' => [
                'sometimes',
                'boolean',
            ],
            'department_permissions.*.can_create_tasks' => [
                'sometimes',
                'boolean',
            ],
            'department_permissions.*.can_edit_all' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'Username can only contain letters, numbers, and underscores.',
            'username.unique' => 'This username is already taken.',
            'email.unique' => 'This email is already registered.',
        ];
    }
}

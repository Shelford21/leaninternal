<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|max:255',
            'username' => 'sometimes|unique:users,username,' . $this->route('user') . '|max:255',
            'employee_number' => 'sometimes|unique:users,employee_number,' . $this->route('user') . '|max:20',
            'password' => 'sometimes|min:6|confirmed',
            'role_id' => 'sometimes|exists:roles,id',
            'description' => 'nullable',
        ];
    }
}

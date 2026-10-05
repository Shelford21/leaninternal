<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'username' => 'required|unique:users,username|max:255',
            'employee_number' => 'required|unique:users,employee_number|max:20',
            'password' => 'required|min:6|confirmed',
            'role_id' => 'required|exists:roles,id',
            'description' => 'nullable',
        ];
    }
}

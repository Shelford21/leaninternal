<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_name' => 'sometimes|max:255',
            'factory_id' => 'sometimes|exists:factories,id',
            'desription' => 'nullable|max:500',
        ];
    }
}

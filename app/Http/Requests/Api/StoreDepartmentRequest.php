<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_name' => 'required|max:255',
            'factory_id' => 'required|exists:factories,id',
            'desription' => 'nullable|max:500',
        ];
    }
}

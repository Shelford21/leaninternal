<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreOperatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_number' => 'required|unique:operators,employee_number|max:20',
            'operator_name' => 'required|max:100',
            'status' => 'in:active,inactive',
        ];
    }
}

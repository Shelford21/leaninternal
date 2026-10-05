<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOperatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_number' => 'sometimes|unique:operators,employee_number,' . $this->route('operator') . '|max:20',
            'operator_name' => 'sometimes|max:100',
            'status' => 'in:active,inactive',
        ];
    }
}

<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSewingFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'factor_name' => 'sometimes|max:100',
            'code' => 'sometimes|unique:sewing_factors,code,' . $this->route('sewing_factor') . '|max:50',
            'factor_value' => 'sometimes|numeric|min:0',
            'description' => 'nullable',
            'status' => 'in:active,inactive',
        ];
    }
}

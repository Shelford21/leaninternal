<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreSewingFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'factor_name' => 'required|max:100',
            'code' => 'required|unique:sewing_factors,code|max:50',
            'factor_value' => 'required|numeric|min:0',
            'description' => 'nullable',
            'status' => 'in:active,inactive',
        ];
    }
}

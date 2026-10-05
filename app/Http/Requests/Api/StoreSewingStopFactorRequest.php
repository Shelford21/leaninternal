<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreSewingStopFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'factor_name' => 'required|max:150',
            'code' => 'required|unique:sewing_stop_factors,code|max:50',
            'factor_value' => 'required|numeric|min:0',
            'description' => 'nullable',
            'tolerance' => 'nullable',
            'status' => 'in:active,inactive',
        ];
    }
}

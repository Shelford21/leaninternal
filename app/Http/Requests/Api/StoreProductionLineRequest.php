<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductionLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'line_name' => 'required|max:255',
            'division_id' => 'required|exists:divisions,id',
            'description' => 'nullable',
        ];
    }
}

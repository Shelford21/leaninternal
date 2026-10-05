<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductionLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'line_name' => 'sometimes|max:255',
            'division_id' => 'sometimes|exists:divisions,id',
            'description' => 'nullable',
        ];
    }
}

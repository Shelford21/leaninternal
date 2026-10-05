<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGsdElementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gsd_category_id' => 'sometimes|exists:gsd_categories,id',
            'element_name' => 'sometimes|max:200',
            'code' => 'sometimes|max:50',
            'tmu' => 'sometimes|numeric|min:0',
            'seconds' => 'sometimes|numeric|min:0',
            'description' => 'nullable',
            'motion_sequence' => 'nullable',
            'status' => 'in:active,inactive',
        ];
    }
}

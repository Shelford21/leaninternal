<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreGsdElementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gsd_category_id' => 'required|exists:gsd_categories,id',
            'element_name' => 'required|max:200',
            'code' => 'required|max:50',
            'tmu' => 'required|numeric|min:0',
            'seconds' => 'required|numeric|min:0',
            'description' => 'nullable',
            'motion_sequence' => 'nullable',
            'status' => 'in:active,inactive',
        ];
    }
}

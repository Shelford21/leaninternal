<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMtmElementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'element_name' => 'sometimes|max:200',
            'code' => 'sometimes|unique:mtm_elements,code,' . $this->route('mtm_element') . '|max:50',
            'tmu' => 'sometimes|numeric|min:0',
            'seconds' => 'sometimes|numeric|min:0',
            'description' => 'nullable',
            'status' => 'in:active,inactive',
        ];
    }
}

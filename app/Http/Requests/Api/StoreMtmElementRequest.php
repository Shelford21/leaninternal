<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreMtmElementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'element_name' => 'required|max:200',
            'code' => 'required|unique:mtm_elements,code|max:50',
            'tmu' => 'required|numeric|min:0',
            'seconds' => 'required|numeric|min:0',
            'description' => 'nullable',
            'status' => 'in:active,inactive',
        ];
    }
}

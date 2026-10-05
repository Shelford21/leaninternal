<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'process_name' => 'required|unique:processes,process_name|max:200',
            'description' => 'nullable',
            'status' => 'in:active,inactive',
        ];
    }
}

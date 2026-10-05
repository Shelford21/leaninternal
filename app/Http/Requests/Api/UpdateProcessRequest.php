<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'process_name' => 'sometimes|unique:processes,process_name,' . $this->route('process') . '|max:200',
            'description' => 'nullable',
            'status' => 'in:active,inactive',
        ];
    }
}

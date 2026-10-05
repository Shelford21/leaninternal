<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProcessVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'process_id' => 'sometimes|exists:processes,id',
            'notes' => 'nullable|max:255',
            'status' => 'in:draft,active,archived',
        ];
    }
}

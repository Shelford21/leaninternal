<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'article_name' => 'sometimes|max:150',
            'label_number' => 'sometimes|unique:articles,label_number,' . $this->route('article') . '|max:100',
            'destination' => 'sometimes|max:100',
            'description' => 'nullable',
            'status' => 'in:active,inactive',
        ];
    }
}

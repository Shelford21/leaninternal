<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'article_name' => 'required|max:150',
            'label_number' => 'required|unique:articles,label_number|max:100',
            'destination' => 'required|max:100',
            'description' => 'nullable',
            'status' => 'in:active,inactive',
        ];
    }
}

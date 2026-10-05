<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGsdCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_name' => 'sometimes|unique:gsd_categories,category_name,' . $this->route('gsd_category') . '|max:150',
            'description' => 'nullable',
        ];
    }
}

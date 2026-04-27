<?php

namespace Modules\Posts\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:post_categories,slug,' . $id,
            'description' => 'nullable|string',
        ];
    }
}

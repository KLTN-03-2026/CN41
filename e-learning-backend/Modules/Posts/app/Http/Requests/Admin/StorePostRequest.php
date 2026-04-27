<?php

namespace Modules\Posts\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:posts,slug',
            'content' => 'required|string',
            'thumbnail' => 'nullable|string',
            'post_category_id' => 'nullable|exists:post_categories,id',
            'is_published' => 'boolean',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ];
    }
}

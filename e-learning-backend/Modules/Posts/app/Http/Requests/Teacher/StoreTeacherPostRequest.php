<?php

namespace Modules\Posts\Http\Requests\Teacher;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreTeacherPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:posts,slug|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            'content' => 'required|string',
            'post_category_id' => 'nullable|exists:post_categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Tiêu đề không được để trống.',
            'title.max' => 'Tiêu đề không quá 255 ký tự.',
            'slug.required' => 'Slug không được để trống.',
            'slug.unique' => 'Slug này đã được sử dụng.',
            'slug.regex' => 'Slug chỉ được chứa chữ thường, số và dấu gạch ngang.',
            'content.required' => 'Nội dung không được để trống.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ.',
            'errors' => $validator->errors(),
        ], 422));
    }
}

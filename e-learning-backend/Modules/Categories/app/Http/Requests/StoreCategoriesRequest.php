<?php

namespace Modules\Categories\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCategoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:255',
            'status' => 'nullable|integer|in:0,1',
            'order' => 'nullable|integer|min:0',
            'parent_id' => 'nullable|integer|exists:categories,id,deleted_at,NULL',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên danh mục là bắt buộc.',
            'name.max' => 'Tên danh mục tối đa 255 ký tự.',
            'slug.required' => 'Slug là bắt buộc.',
            'slug.unique' => 'Slug đã tồn tại.',
            'slug.regex' => 'Slug chỉ chứa chữ thường, số và dấu gạch ngang.',
            'description.max' => 'Mô tả tối đa 1000 ký tự.',
            'status.in' => 'Trạng thái chỉ có thể là 0 hoặc 1.',
            'order.min' => 'Thứ tự không được nhỏ hơn 0.',
            'parent_id.exists' => 'Danh mục cha không tồn tại.',
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

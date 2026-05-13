<?php

namespace Modules\Course\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class IndexCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|integer|in:0,1',
            'teacher_id' => 'nullable|integer|exists:teachers,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'level' => 'nullable|string|in:beginner,intermediate,advanced',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Trạng thái chỉ có thể là 0 hoặc 1.',
            'teacher_id.exists' => 'Giảng viên không tồn tại.',
            'category_id.exists' => 'Danh mục không tồn tại.',
            'level.in' => 'Trình độ phải là: beginner, intermediate, hoặc advanced.',
            'per_page.min' => 'Số bản ghi tối thiểu là 1.',
            'per_page.max' => 'Số bản ghi tối đa là 100.',
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

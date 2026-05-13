<?php

namespace Modules\Lessons\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class IndexLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|in:0,1',
            'type' => 'nullable|in:video,document,text,quiz',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Trạng thái chỉ có thể là 0 hoặc 1.',
            'type.in' => 'Loại bài giảng phải là: video, document, text hoặc quiz.',
            'per_page.integer' => 'Số lượng trên trang phải là số nguyên.',
            'per_page.min' => 'Số lượng trên trang tối thiểu là 1.',
            'per_page.max' => 'Số lượng trên trang tối đa là 100.',
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

<?php

namespace Modules\Quiz\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'lesson_id' => 'required|exists:lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'max_attempts' => 'nullable|integer|min:1|max:10',
            'time_limit' => 'nullable|integer|min:1',
            'status' => 'nullable|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'lesson_id.required' => 'Bài học không được để trống.',
            'lesson_id.exists' => 'Bài học không tồn tại.',
            'title.required' => 'Tiêu đề quiz không được để trống.',
            'title.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
            'max_attempts.min' => 'Số lần thử tối thiểu là 1.',
            'max_attempts.max' => 'Số lần thử tối đa là 10.',
            'time_limit.min' => 'Thời gian tối thiểu là 1 phút.',
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

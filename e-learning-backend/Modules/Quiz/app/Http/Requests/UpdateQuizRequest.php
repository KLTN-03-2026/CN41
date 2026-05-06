<?php

namespace Modules\Quiz\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'max_attempts' => 'nullable|integer|min:1|max:10',
            'time_limit' => 'nullable|integer|min:1',
            'status' => 'nullable|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
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

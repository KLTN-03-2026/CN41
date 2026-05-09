<?php

namespace Modules\Quiz\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GenerateQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'source' => 'required|in:upload,chapter',
            'count' => 'nullable|integer|min:1|max:20',
            'file' => 'required_if:source,upload|nullable|file|mimes:pdf|max:20480',
            'custom_prompt' => 'nullable|string|max:500',
            'max_attempts' => 'nullable|integer|min:1|max:10',
            'time_limit' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'source.required' => 'Nguồn dữ liệu không được để trống.',
            'source.in' => 'Nguồn dữ liệu không hợp lệ (upload hoặc chapter).',
            'count.integer' => 'Số câu hỏi phải là số nguyên.',
            'count.min' => 'Số câu hỏi tối thiểu là 1.',
            'count.max' => 'Số câu hỏi tối đa là 20.',
            'file.required_if' => 'Vui lòng upload file PDF.',
            'file.mimes' => 'Chỉ chấp nhận file PDF.',
            'file.max' => 'File PDF không được vượt quá 20MB.',
            'custom_prompt.max' => 'Yêu cầu bổ sung không được vượt quá 500 ký tự.',
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

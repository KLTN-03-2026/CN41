<?php

namespace Modules\Quiz\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubmitQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'answers' => 'required|array',
            'answers.*' => 'in:A,B,C,D',
        ];
    }

    public function messages(): array
    {
        return [
            'answers.required' => 'Phải có ít nhất một câu trả lời.',
            'answers.*.in' => 'Câu trả lời không hợp lệ (phải là A, B, C hoặc D).',
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

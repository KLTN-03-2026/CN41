<?php

namespace Modules\Quiz\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateQuizQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'question' => 'sometimes|string',
            'option_a' => 'sometimes|string',
            'option_b' => 'sometimes|string',
            'option_c' => 'sometimes|string',
            'option_d' => 'sometimes|string',
            'correct_option' => 'sometimes|in:A,B,C,D',
        ];
    }

    public function messages(): array
    {
        return [
            'correct_option.in' => 'Đáp án đúng phải là A, B, C hoặc D.',
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

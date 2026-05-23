<?php

namespace Modules\Commission\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConfirmEmailChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'otp' => 'required|string|size:6',
        ];
    }

    public function messages(): array
    {
        return [
            'otp.required' => 'Vui lòng nhập mã xác minh.',
            'otp.size' => 'Mã xác minh phải có đúng 6 ký tự.',
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

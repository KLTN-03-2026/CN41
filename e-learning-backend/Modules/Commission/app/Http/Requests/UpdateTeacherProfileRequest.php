<?php

namespace Modules\Commission\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateTeacherProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'nullable|string|max:1000',
            'bank_name' => 'nullable|string|max:255',
            'image' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'description.max' => 'Giới thiệu không được vượt quá 1000 ký tự.',
            'bank_name.max' => 'Tên ngân hàng không được vượt quá 255 ký tự.',
            'bank_account_number.max' => 'Số tài khoản không được vượt quá 50 ký tự.',
            'bank_account_name.max' => 'Tên chủ tài khoản không được vượt quá 255 ký tự.',
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

<?php

namespace Modules\Commission\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SendEmailChangeOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'new_email' => 'required|email|unique:users,email',
        ];
    }

    public function messages(): array
    {
        return [
            'new_email.required' => 'Vui lòng nhập email mới.',
            'new_email.email' => 'Địa chỉ email không hợp lệ.',
            'new_email.unique' => 'Email này đã được sử dụng.',
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

<?php

namespace Modules\Payment\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class MyOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'per_page' => 'nullable|integer|min:1|max:100',
            'status' => 'nullable|string|in:pending,paid,failed,cancelled,refunded',
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.integer' => 'Số bản ghi mỗi trang phải là số nguyên.',
            'status.in' => 'Trạng thái không hợp lệ.',
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

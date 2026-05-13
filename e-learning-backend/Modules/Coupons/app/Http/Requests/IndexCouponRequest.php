<?php

namespace Modules\Coupons\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class IndexCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|integer|in:0,1',
            'type' => 'nullable|string|in:fixed,percentage',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Trạng thái chỉ có thể là 0 hoặc 1.',
            'type.in' => 'Loại giảm giá phải là "fixed" hoặc "percentage".',
            'per_page.integer' => 'per_page phải là số nguyên.',
            'per_page.min' => 'per_page tối thiểu là 1.',
            'per_page.max' => 'per_page tối đa là 100.',
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

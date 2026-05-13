<?php

namespace Modules\Coupons\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ValidateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50',
            'subtotal' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Mã giảm giá là bắt buộc.',
            'code.max' => 'Mã giảm giá tối đa 50 ký tự.',
            'subtotal.required' => 'Giá trị đơn hàng là bắt buộc.',
            'subtotal.numeric' => 'Giá trị đơn hàng phải là số.',
            'subtotal.min' => 'Giá trị đơn hàng không được âm.',
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

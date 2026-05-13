<?php

namespace Modules\Payment\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BulkDeleteOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:orders,id',
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Danh sách đơn hàng là bắt buộc.',
            'ids.array' => 'Danh sách phải là mảng.',
            'ids.min' => 'Phải chọn ít nhất một đơn hàng.',
            'ids.*.integer' => 'ID đơn hàng phải là số nguyên.',
            'ids.*.exists' => 'Một hoặc nhiều đơn hàng không tồn tại.',
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

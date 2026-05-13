<?php

namespace Modules\Lessons\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReorderSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'orders' => 'required|array',
            'orders.*.id' => 'required|integer|exists:sections,id',
            'orders.*.order' => 'required|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'orders.required' => 'Danh sách thứ tự là bắt buộc.',
            'orders.array' => 'Danh sách thứ tự phải là mảng.',
            'orders.*.id.required' => 'ID chương là bắt buộc.',
            'orders.*.id.integer' => 'ID chương phải là số nguyên.',
            'orders.*.id.exists' => 'Chương không tồn tại.',
            'orders.*.order.required' => 'Thứ tự là bắt buộc.',
            'orders.*.order.integer' => 'Thứ tự phải là số nguyên.',
            'orders.*.order.min' => 'Thứ tự không được nhỏ hơn 0.',
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

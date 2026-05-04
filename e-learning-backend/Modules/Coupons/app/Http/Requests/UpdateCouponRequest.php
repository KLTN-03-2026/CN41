<?php

namespace Modules\Coupons\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        // apiResource đặt tên param là {coupon}, không phải {id}
        $id = $this->route('coupon');

        return [
            'code' => "required|string|max:50|unique:coupons,code,{$id}",
            'type' => 'required|string|in:fixed,percentage',
            'value' => 'required|numeric|min:0',
            'min_order_value' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => ['nullable', 'date', function ($attribute, $value, $fail) {
                $startDate = $this->input('start_date');
                if ($startDate && $value < $startDate) {
                    $fail('Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.');
                }
            }],
            'status' => 'nullable|integer|in:0,1',
            'description' => 'nullable|string|max:1000',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(trim($this->code)),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Mã giảm giá là bắt buộc.',
            'code.unique' => 'Mã giảm giá đã tồn tại.',
            'code.max' => 'Mã giảm giá tối đa 50 ký tự.',
            'type.required' => 'Loại giảm giá là bắt buộc.',
            'type.in' => 'Loại giảm giá phải là "fixed" hoặc "percentage".',
            'value.required' => 'Giá trị giảm giá là bắt buộc.',
            'value.numeric' => 'Giá trị giảm giá phải là số.',
            'value.min' => 'Giá trị giảm giá không được nhỏ hơn 0.',
            'min_order_value.numeric' => 'Giá trị đơn tối thiểu phải là số.',
            'max_discount.numeric' => 'Giảm tối đa phải là số.',
            'usage_limit.integer' => 'Giới hạn sử dụng phải là số nguyên.',
            'usage_limit.min' => 'Giới hạn sử dụng phải ít nhất 1.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
            'description.max' => 'Mô tả tối đa 1000 ký tự.',
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

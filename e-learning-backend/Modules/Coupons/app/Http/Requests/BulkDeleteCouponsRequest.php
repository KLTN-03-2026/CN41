<?php

namespace Modules\Coupons\Http\Requests;

use Illuminate\Validation\Validator;

class BulkDeleteCouponsRequest extends BaseBulkRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'ids'   => 'required|array|min:1|max:100',
            'ids.*' => 'required|integer|exists:coupons,id',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) return;

            $alreadyTrashed = \Modules\Coupons\Models\Coupon::onlyTrashed()
                ->whereIn('id', $this->ids)
                ->pluck('id')
                ->toArray();

            if (!empty($alreadyTrashed)) {
                $validator->errors()->add(
                    'ids',
                    'Các mã giảm giá sau đã bị xoá: ' . implode(', ', $alreadyTrashed)
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'ids.required'  => 'Danh sách ID không được để trống.',
            'ids.array'     => 'ids phải là mảng.',
            'ids.min'       => 'Phải chọn ít nhất 1 mã giảm giá.',
            'ids.max'       => 'Không thể xử lý quá 100 mã giảm giá cùng lúc.',
            'ids.*.integer' => 'ID phải là số nguyên.',
            'ids.*.exists'  => 'Một hoặc nhiều mã giảm giá không tồn tại.',
        ];
    }
}

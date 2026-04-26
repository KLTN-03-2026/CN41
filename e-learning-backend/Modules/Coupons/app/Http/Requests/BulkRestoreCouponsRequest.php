<?php

namespace Modules\Coupons\Http\Requests;

class BulkRestoreCouponsRequest extends BaseBulkRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'ids'   => 'required|array|min:1|max:100',
            'ids.*' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required'  => 'Danh sách ID không được để trống.',
            'ids.array'     => 'ids phải là mảng.',
            'ids.min'       => 'Phải chọn ít nhất 1 mã giảm giá.',
            'ids.max'       => 'Không thể xử lý quá 100 mã giảm giá cùng lúc.',
            'ids.*.integer' => 'ID phải là số nguyên.',
        ];
    }
}

<?php

namespace Modules\Students\Http\Requests;

class BulkForceDeleteStudentsRequest extends BaseBulkRequest
{
    public function authorize(): bool
    {
        return true;
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
            'ids.min'       => 'Phải chọn ít nhất 1 student.',
            'ids.max'       => 'Không thể xử lý quá 100 student cùng lúc.',
            'ids.*.integer' => 'ID phải là số nguyên.',
        ];
    }
}

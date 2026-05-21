<?php

namespace Modules\Commission\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCommissionSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'teacher_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'teacher_rate.required' => 'Tỷ lệ hoa hồng là bắt buộc.',
            'teacher_rate.numeric' => 'Tỷ lệ hoa hồng phải là số.',
            'teacher_rate.min' => 'Tỷ lệ hoa hồng tối thiểu là 0%.',
            'teacher_rate.max' => 'Tỷ lệ hoa hồng tối đa là 100%.',
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

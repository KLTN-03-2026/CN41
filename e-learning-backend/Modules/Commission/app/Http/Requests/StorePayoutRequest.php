<?php

namespace Modules\Commission\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePayoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount'       => ['required', 'numeric', 'min:1000'],
            'teacher_note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return ['amount.min' => 'Số tiền tối thiểu là 1,000 VNĐ.'];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}

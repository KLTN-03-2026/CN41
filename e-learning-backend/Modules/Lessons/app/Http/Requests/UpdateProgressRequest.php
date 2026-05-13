<?php

namespace Modules\Lessons\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'watched_seconds' => 'required|integer|min:0',
            'is_completed' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'watched_seconds.required' => 'Thời gian xem là bắt buộc.',
            'watched_seconds.integer' => 'Thời gian xem phải là số nguyên.',
            'watched_seconds.min' => 'Thời gian xem không được nhỏ hơn 0.',
            'is_completed.boolean' => 'Trạng thái hoàn thành phải là true hoặc false.',
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

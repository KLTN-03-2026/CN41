<?php

namespace Modules\Students\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class IndexStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'search.max' => 'Từ khóa tìm kiếm tối đa 100 ký tự.',
            'per_page.integer' => 'Số lượng mỗi trang phải là số nguyên.',
            'per_page.min' => 'Số lượng mỗi trang tối thiểu là 1.',
            'per_page.max' => 'Số lượng mỗi trang tối đa là 100.',
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

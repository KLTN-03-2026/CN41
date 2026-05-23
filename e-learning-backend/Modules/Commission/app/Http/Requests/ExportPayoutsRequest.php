<?php

namespace Modules\Commission\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ExportPayoutsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d|after_or_equal:from',
            'status' => 'nullable|in:pending,approved,rejected,paid',
        ];
    }

    public function messages(): array
    {
        return [
            'from.date_format' => 'Ngày bắt đầu không đúng định dạng (YYYY-MM-DD).',
            'to.date_format' => 'Ngày kết thúc không đúng định dạng (YYYY-MM-DD).',
            'to.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
            'status.in' => 'Trạng thái không hợp lệ.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Tham số không hợp lệ.',
            'errors' => $validator->errors(),
        ], 422));
    }
}

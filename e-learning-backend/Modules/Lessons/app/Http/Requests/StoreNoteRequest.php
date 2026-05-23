<?php

namespace Modules\Lessons\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:10000'],
            'timestamp_seconds' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Nội dung ghi chú không được để trống.',
            'content.max' => 'Ghi chú không được vượt quá 10.000 ký tự.',
            'timestamp_seconds.integer' => 'Mốc thời gian không hợp lệ.',
            'timestamp_seconds.min' => 'Mốc thời gian không được âm.',
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

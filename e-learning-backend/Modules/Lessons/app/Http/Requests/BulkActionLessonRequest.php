<?php

namespace Modules\Lessons\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BulkActionLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:lessons,id',
            'action' => 'required|string|in:publish,unpublish,activate,deactivate,assign-section',
            'section_id' => 'nullable|integer|exists:sections,id',
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Danh sách ID là bắt buộc.',
            'ids.array' => 'Danh sách ID phải là mảng.',
            'ids.min' => 'Phải chọn ít nhất 1 bài giảng.',
            'ids.*.integer' => 'ID bài giảng phải là số nguyên.',
            'ids.*.exists' => 'Bài giảng không tồn tại.',
            'action.required' => 'Hành động là bắt buộc.',
            'action.in' => 'Hành động không hợp lệ.',
            'section_id.integer' => 'ID chương phải là số nguyên.',
            'section_id.exists' => 'Chương không tồn tại.',
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

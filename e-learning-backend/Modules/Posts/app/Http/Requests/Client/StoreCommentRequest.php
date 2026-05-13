<?php

namespace Modules\Posts\Http\Requests\Client;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content'   => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:post_comments,id',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Nội dung bình luận là bắt buộc.',
            'content.max'      => 'Nội dung bình luận không được vượt quá 1000 ký tự.',
            'parent_id.exists' => 'Bình luận cha không tồn tại.',
        ];
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

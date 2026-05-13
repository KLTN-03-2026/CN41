<?php

namespace Modules\Upload\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UploadImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'file' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'folder' => 'nullable|string|in:images,thumbnails,avatars,banners',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Vui lòng chọn file hình ảnh.',
            'file.image' => 'File phải là hình ảnh.',
            'file.mimes' => 'Chỉ chấp nhận định dạng: JPG, JPEG, PNG, WebP.',
            'file.max' => 'Dung lượng hình ảnh tối đa 5MB.',
            'folder.in' => 'Thư mục không hợp lệ.',
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

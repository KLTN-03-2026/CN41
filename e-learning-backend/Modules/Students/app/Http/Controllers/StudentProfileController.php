<?php

namespace Modules\Students\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Modules\Students\Http\Requests\ChangePasswordRequest;
use Modules\Students\Http\Requests\UpdateMyProfileRequest;
use Modules\Students\Http\Resources\StudentResource;

class StudentProfileController extends Controller
{
    use ApiResponse;

    /**
     * Lấy thông tin profile học viên hiện tại.
     */
    public function show(Request $request): JsonResponse
    {
        return $this->success(new StudentResource($request->user('api')));
    }

    /**
     * Cập nhật thông tin cá nhân (name, email, date_of_birth).
     */
    public function update(UpdateMyProfileRequest $request): JsonResponse
    {
        $student = $request->user('api');
        $student->update($request->validated());

        return $this->success(new StudentResource($student->fresh()), 'Cập nhật thông tin thành công.');
    }

    /**
     * Upload avatar cho học viên.
     * Xoá file cũ nếu đã tồn tại.
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'file.required' => 'Vui lòng chọn ảnh.',
            'file.image' => 'File phải là ảnh.',
            'file.mimes' => 'Chỉ chấp nhận định dạng jpg, jpeg, png, webp.',
            'file.max' => 'Ảnh không được vượt quá 2MB.',
        ]);

        $student = $request->user('api');

        // Xoá avatar cũ nếu là file local (không phải URL ngoài)
        if ($student->avatar) {
            $oldPath = str_replace(asset('storage').'/', '', $student->avatar);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $path = $request->file('file')->store('avatars', 'public');
        $url = asset('storage/'.$path);

        $student->update(['avatar' => $url]);

        return $this->success(['avatar' => $url], 'Cập nhật avatar thành công.');
    }

    /**
     * Đổi mật khẩu qua email xác nhận.
     * Xác thực mật khẩu hiện tại → gửi link reset qua Password Broker.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $student = $request->user('api');

        if (! Hash::check($request->current_password, $student->password)) {
            return $this->error('Mật khẩu hiện tại không đúng.', 422, [
                'current_password' => ['Mật khẩu hiện tại không đúng.'],
            ]);
        }

        $status = Password::broker('students')->sendResetLink(['email' => $student->email]);

        if ($status === Password::RESET_THROTTLED) {
            return $this->error('Vui lòng chờ trước khi gửi lại.', 429);
        }

        if ($status !== Password::RESET_LINK_SENT) {
            return $this->error('Không thể gửi email. Vui lòng thử lại sau.', 500);
        }

        return $this->success(null, 'Vui lòng kiểm tra email để xác nhận đổi mật khẩu.');
    }
}

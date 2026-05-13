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
use Modules\Students\Http\Requests\UploadAvatarRequest;
use Modules\Students\Http\Resources\StudentResource;
use Modules\Students\Repositories\StudentsRepositoryInterface;

class StudentProfileController extends Controller
{
    use ApiResponse;

    public function __construct(private StudentsRepositoryInterface $repository) {}

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
        $updated = $this->repository->update($student->id, $request->validated());

        return $this->success(new StudentResource($updated), 'Cập nhật thông tin thành công.');
    }

    /**
     * Upload avatar cho học viên.
     * Xoá file cũ nếu đã tồn tại.
     */
    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $student = $request->user('api');

        if ($student->avatar) {
            $oldPath = str_replace(asset('storage').'/', '', $student->avatar);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $path = $request->file('file')->store('avatars', 'public');
        $url = asset('storage/'.$path);

        $this->repository->update($student->id, ['avatar' => $url]);

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

<?php

namespace Modules\Auth\Http\Controllers\Admin;

use App\Events\AdminLoggedIn;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Http\Requests\AdminLoginRequest;
use Modules\Users\Models\User;

/**
 * Admin AuthController
 *
 * Xử lý xác thực cho phía Admin (guard: admin).
 * Endpoints:
 *   POST /api/v1/admin/auth/login   → Đăng nhập
 *   POST /api/v1/admin/auth/logout  → Đăng xuất [auth:admin]
 *   GET  /api/v1/admin/auth/me      → Thông tin admin [auth:admin]
 */
class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Đăng nhập Admin.
     *
     * Validate email + password → kiểm tra credentials → trả token + user info.
     * Token name: 'admin-token' để phân biệt với student token.
     */
    public function login(AdminLoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        // Tìm user theo email
        $user = User::where('email', $credentials['email'])->first();

        // Kiểm tra user tồn tại và password đúng
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return $this->error('Email hoặc mật khẩu không đúng.', 401);
        }

        // Tạo Sanctum token
        $token = $user->createToken('admin-token')->plainTextToken;

        // Dispatch Event cho Activity Log
        event(new AdminLoggedIn($user, $request->ip(), $request->userAgent() ?? 'Unknown'));

        $user->load('teacher');

        return $this->success([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->teacher?->image ? asset('storage/'.$user->teacher->image) : ($user->avatar ? asset('storage/'.$user->avatar) : null),
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ], 'Đăng nhập thành công.');
    }

    /**
     * Đăng xuất Admin.
     *
     * Revoke token hiện tại (chỉ token đang dùng, không revoke tất cả).
     */
    public function logout(Request $request): JsonResponse
    {
        // Xoá token hiện tại đang sử dụng (nếu có - linh hoạt cho cả Web/Test)
        $token = $request->user()->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return $this->success(null, 'Đăng xuất thành công.');
    }

    /**
     * Lấy thông tin Admin đang đăng nhập.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('teacher');

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar ? asset('storage/'.$user->avatar) : null,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'created_at' => $user->created_at,
        ];

        // Expose teacher ID so the frontend can subscribe to teacher broadcast channel
        if ($user->teacher) {
            $data['teacher_id'] = $user->teacher->id;
            if ($user->teacher->image) {
                $data['avatar'] = asset('storage/'.$user->teacher->image);
            }
        }

        return $this->success($data);
    }
}

<?php

namespace Modules\Commission\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Modules\Commission\Http\Requests\ConfirmEmailChangeRequest;
use Modules\Commission\Http\Requests\ConfirmPasswordChangeRequest;
use Modules\Commission\Http\Requests\SendEmailChangeOtpRequest;
use Modules\Commission\Http\Requests\UpdateTeacherProfileRequest;
use Modules\Commission\Mail\TeacherOtpMail;
use Modules\Commission\Models\AdminOtpToken;
use Modules\Commission\Repositories\CommissionRepositoryInterface;
use Modules\Course\Http\Resources\CourseResource;
use Modules\Course\Models\Course;
use Modules\Course\Repositories\CourseRepositoryInterface;
use Modules\Teachers\Models\Teachers;

class TeacherPortalController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CommissionRepositoryInterface $repository,
        private CourseRepositoryInterface $courseRepo,
    ) {}

    private function getTeacher(): Teachers
    {
        return Teachers::where('user_id', auth('admin')->id())->firstOrFail();
    }

    private function profileData(Teachers $teacher): array
    {
        return [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'description' => $teacher->description,
            'image' => $teacher->image ? asset('storage/'.$teacher->image) : null,
            'bank_name' => $teacher->bank_name,
            'bank_account_number' => $teacher->bank_account_number,
            'bank_account_name' => $teacher->bank_account_name,
        ];
    }

    public function dashboard(): JsonResponse
    {
        $teacher = $this->getTeacher();
        $courseStats = Course::where('teacher_id', $teacher->id)
            ->selectRaw('COUNT(*) as total_courses, COALESCE(SUM(total_students), 0) as total_students')
            ->first();

        return $this->success([
            'total_courses' => (int) $courseStats->total_courses,
            'total_students' => (int) $courseStats->total_students,
            'total_earned' => $this->repository->getTotalEarned($teacher->id),
            'available_balance' => $this->repository->getAvailableBalance($teacher->id),
        ]);
    }

    public function courses(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));

        // ScopesToTeacher auto-filters to the logged-in teacher's courses
        $courses = $this->courseRepo->getFiltered([], $perPage);
        $courses->setCollection(CourseResource::collection($courses->getCollection())->collection);

        return $this->paginated($courses, 'Lấy danh sách khóa học thành công.');
    }

    public function profile(): JsonResponse
    {
        $teacher = $this->getTeacher();
        $data = $this->profileData($teacher);
        $data['email'] = auth('admin')->user()->email;

        return $this->success($data);
    }

    public function updateProfile(UpdateTeacherProfileRequest $request): JsonResponse
    {
        $teacher = $this->getTeacher();
        $teacher->update($request->validated());
        $teacher->refresh();

        return $this->success($this->profileData($teacher), 'Cập nhật hồ sơ thành công.');
    }

    public function sendPasswordOtp(Request $request): JsonResponse
    {
        $user = auth('admin')->user();

        $latest = AdminOtpToken::where('user_id', $user->id)
            ->where('purpose', 'password_change')
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($latest && $latest->created_at->diffInSeconds(now()) < 60) {
            $wait = 60 - (int) $latest->created_at->diffInSeconds(now());

            return $this->error("Vui lòng chờ {$wait} giây trước khi gửi lại mã.", 429);
        }

        AdminOtpToken::where('user_id', $user->id)
            ->where('purpose', 'password_change')
            ->where('used', false)
            ->delete();

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        AdminOtpToken::create([
            'user_id' => $user->id,
            'otp' => $otp,
            'purpose' => 'password_change',
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($user->email)->send(new TeacherOtpMail($otp, 'password_change', $user->name));

        return $this->success(null, 'Mã xác minh đã được gửi đến email của bạn.');
    }

    public function confirmPasswordChange(ConfirmPasswordChangeRequest $request): JsonResponse
    {
        $user = auth('admin')->user();

        $token = AdminOtpToken::where('user_id', $user->id)
            ->where('purpose', 'password_change')
            ->where('otp', $request->otp)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (! $token) {
            return $this->error('Mã xác minh không hợp lệ hoặc đã hết hạn.', 422);
        }

        $token->update(['used' => true]);
        $user->update(['password' => $request->password]);

        return $this->success(null, 'Đổi mật khẩu thành công.');
    }

    public function sendEmailChangeOtp(SendEmailChangeOtpRequest $request): JsonResponse
    {
        $user = auth('admin')->user();

        $latest = AdminOtpToken::where('user_id', $user->id)
            ->where('purpose', 'email_change')
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($latest && $latest->created_at->diffInSeconds(now()) < 60) {
            $wait = 60 - (int) $latest->created_at->diffInSeconds(now());

            return $this->error("Vui lòng chờ {$wait} giây trước khi gửi lại mã.", 429);
        }

        AdminOtpToken::where('user_id', $user->id)
            ->where('purpose', 'email_change')
            ->where('used', false)
            ->delete();

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        AdminOtpToken::create([
            'user_id' => $user->id,
            'otp' => $otp,
            'purpose' => 'email_change',
            'new_email' => $request->new_email,
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($request->new_email)->send(new TeacherOtpMail($otp, 'email_change', $user->name));

        return $this->success(null, 'Mã xác minh đã được gửi đến email mới của bạn.');
    }

    public function confirmEmailChange(ConfirmEmailChangeRequest $request): JsonResponse
    {
        $user = auth('admin')->user();

        $token = AdminOtpToken::where('user_id', $user->id)
            ->where('purpose', 'email_change')
            ->where('otp', $request->otp)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (! $token) {
            return $this->error('Mã xác minh không hợp lệ hoặc đã hết hạn.', 422);
        }

        $token->update(['used' => true]);
        $user->update(['email' => $token->new_email, 'email_verified_at' => now()]);

        return $this->success(['email' => $token->new_email], 'Đổi email thành công.');
    }
}

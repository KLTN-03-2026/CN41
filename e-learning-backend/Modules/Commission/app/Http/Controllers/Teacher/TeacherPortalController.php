<?php

namespace Modules\Commission\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Commission\Http\Requests\UpdateTeacherProfileRequest;
use Modules\Commission\Repositories\CommissionRepositoryInterface;
use Modules\Course\Models\Course;
use Modules\Teachers\Models\Teachers;

class TeacherPortalController extends Controller
{
    use ApiResponse;

    public function __construct(private CommissionRepositoryInterface $repository) {}

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
        $teacher = $this->getTeacher();
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));

        $courses = Course::where('teacher_id', $teacher->id)
            ->with('categories')
            ->latest()
            ->paginate($perPage);

        return $this->paginated($courses);
    }

    public function profile(): JsonResponse
    {
        return $this->success($this->profileData($this->getTeacher()));
    }

    public function updateProfile(UpdateTeacherProfileRequest $request): JsonResponse
    {
        $teacher = $this->getTeacher();
        $teacher->update($request->validated());
        $teacher->refresh();

        return $this->success($this->profileData($teacher), 'Cập nhật hồ sơ thành công.');
    }
}

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

    public function dashboard(): JsonResponse
    {
        $teacher = $this->getTeacher();

        return $this->success([
            'total_courses' => Course::where('teacher_id', $teacher->id)->count(),
            'total_students' => (int) Course::where('teacher_id', $teacher->id)->sum('total_students'),
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
        $teacher = $this->getTeacher();

        return $this->success([
            'id' => $teacher->id,
            'name' => $teacher->name,
            'description' => $teacher->description,
            'image' => $teacher->image ? asset('storage/'.$teacher->image) : null,
            'bank_name' => $teacher->bank_name,
            'bank_account_number' => $teacher->bank_account_number,
            'bank_account_name' => $teacher->bank_account_name,
        ]);
    }

    public function updateProfile(UpdateTeacherProfileRequest $request): JsonResponse
    {
        $teacher = $this->getTeacher();
        $teacher->update($request->validated());

        return $this->success([
            'id' => $teacher->fresh()->id,
            'name' => $teacher->fresh()->name,
            'description' => $teacher->fresh()->description,
            'image' => $teacher->fresh()->image ? asset('storage/'.$teacher->fresh()->image) : null,
            'bank_name' => $teacher->fresh()->bank_name,
            'bank_account_number' => $teacher->fresh()->bank_account_number,
            'bank_account_name' => $teacher->fresh()->bank_account_name,
        ], 'Cập nhật hồ sơ thành công.');
    }
}

<?php

namespace Modules\Commission\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Commission\Exports\TeacherEarningsExport;
use Modules\Commission\Http\Requests\ExportEarningsRequest;
use Modules\Commission\Http\Requests\StorePayoutRequest;
use Modules\Commission\Http\Resources\TeacherEarningResource;
use Modules\Commission\Http\Resources\TeacherPayoutResource;
use Modules\Commission\Models\TeacherPayout;
use Modules\Commission\Repositories\CommissionRepositoryInterface;
use Modules\Notifications\Services\NotificationService;
use Modules\Teachers\Models\Teachers;
use Modules\Users\Models\User;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EarningsController extends Controller
{
    use ApiResponse;

    public function __construct(private CommissionRepositoryInterface $repository) {}

    public function export(ExportEarningsRequest $request): BinaryFileResponse
    {
        $user = auth('admin')->user();
        $teacher = Teachers::where('user_id', $user->id)->firstOrFail();

        $from = $request->query('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->query('to', now()->format('Y-m-d'));

        $filename = "thu-nhap_{$from}_{$to}.xlsx";

        return Excel::download(
            new TeacherEarningsExport(
                from: $from,
                to: $to,
                teacherId: $teacher->id,
                showTeacherColumn: false,
            ),
            $filename
        );
    }

    public function index(Request $request): JsonResponse
    {
        $user = auth('admin')->user();
        $teacher = Teachers::where('user_id', $user->id)->firstOrFail();
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));

        $earnings = $this->repository->getEarningsForTeacher($teacher->id, $perPage);
        $earnings->setCollection(TeacherEarningResource::collection($earnings->getCollection())->collection);

        return $this->success([
            'balance' => [
                'available' => $this->repository->getAvailableBalance($teacher->id),
                'total_earned' => $this->repository->getTotalEarned($teacher->id),
                'total_paid' => $this->repository->getTotalPaid($teacher->id),
                'pending_payout' => $this->repository->getPendingPayoutAmount($teacher->id),
            ],
            'earnings' => $earnings->items(),
            'pagination' => [
                'current_page' => $earnings->currentPage(),
                'last_page' => $earnings->lastPage(),
                'per_page' => $earnings->perPage(),
                'total' => $earnings->total(),
            ],
        ]);
    }

    public function myPayouts(Request $request): JsonResponse
    {
        $user = auth('admin')->user();
        $teacher = Teachers::where('user_id', $user->id)->firstOrFail();
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));

        $data = TeacherPayout::where('teacher_id', $teacher->id)->latest()->paginate($perPage);
        $data->setCollection(TeacherPayoutResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    public function requestPayout(StorePayoutRequest $request): JsonResponse
    {
        $user = auth('admin')->user();
        $teacher = Teachers::where('user_id', $user->id)->firstOrFail();

        $payout = DB::transaction(function () use ($request, $teacher) {
            // Lock teacher row to serialize concurrent payout requests from the same teacher
            Teachers::lockForUpdate()->findOrFail($teacher->id);

            $available = $this->repository->getAvailableBalance($teacher->id);

            if ($request->amount > $available) {
                throw new HttpResponseException(
                    $this->error('Số dư khả dụng không đủ. Hiện có: '.number_format($available).' VNĐ.', 422)
                );
            }

            return TeacherPayout::create([
                'teacher_id' => $teacher->id,
                'amount' => $request->amount,
                'teacher_note' => $request->teacher_note,
                'status' => 'pending',
            ]);
        });

        // Notify all admin/super-admin users about the new payout request
        try {
            $notificationService = app(NotificationService::class);
            User::role(['admin', 'super-admin'])->get()->each(function ($admin) use ($notificationService, $teacher, $payout) {
                $notificationService->notifyPayoutRequest(
                    adminId: $admin->id,
                    teacherName: $teacher->name,
                    amount: (float) $payout->amount,
                    payoutId: $payout->id,
                );
            });
        } catch (\Throwable) {
            // Notifications are non-critical
        }

        return $this->success(new TeacherPayoutResource($payout), 'Yêu cầu rút tiền đã được gửi.', 201);
    }
}

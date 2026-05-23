<?php

namespace Modules\Commission\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Commission\Http\Resources\TeacherPayoutResource;
use Modules\Commission\Models\TeacherPayout;
use Modules\Notifications\Services\NotificationService;

class PayoutController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));
        $query = TeacherPayout::with('teacher')->latest();

        $validStatuses = ['pending', 'approved', 'rejected', 'paid'];
        if ($request->filled('status') && in_array($request->query('status'), $validStatuses, true)) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->query('teacher_id'));
        }

        $data = $query->paginate($perPage);
        $data->setCollection(TeacherPayoutResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $payout = TeacherPayout::findOrFail($id);

        if ($payout->status !== 'pending') {
            return $this->error('Chỉ có thể duyệt yêu cầu đang chờ.', 422);
        }

        $payout->update(['status' => 'approved', 'admin_note' => $request->input('admin_note'), 'processed_at' => now()]);

        try {
            app(NotificationService::class)->notifyPayoutDecision(
                teacherId: $payout->teacher_id,
                status: 'approved',
                amount: (float) $payout->amount,
                payoutId: $payout->id,
            );
        } catch (\Throwable) {
        }

        return $this->success(new TeacherPayoutResource($payout->load('teacher')), 'Yêu cầu đã được duyệt.');
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $payout = TeacherPayout::findOrFail($id);

        if (! in_array($payout->status, ['pending', 'approved'])) {
            return $this->error('Không thể từ chối yêu cầu này.', 422);
        }

        $payout->update(['status' => 'rejected', 'admin_note' => $request->input('admin_note'), 'processed_at' => now()]);

        try {
            app(NotificationService::class)->notifyPayoutDecision(
                teacherId: $payout->teacher_id,
                status: 'rejected',
                amount: (float) $payout->amount,
                payoutId: $payout->id,
            );
        } catch (\Throwable) {
        }

        return $this->success(new TeacherPayoutResource($payout->load('teacher')), 'Yêu cầu đã bị từ chối.');
    }

    public function markPaid(int $id): JsonResponse
    {
        $payout = TeacherPayout::findOrFail($id);

        if ($payout->status !== 'approved') {
            return $this->error('Chỉ có thể đánh dấu đã thanh toán cho yêu cầu đã duyệt.', 422);
        }

        $payout->update(['status' => 'paid', 'processed_at' => now()]);

        return $this->success(new TeacherPayoutResource($payout->load('teacher')), 'Đã đánh dấu thanh toán.');
    }
}

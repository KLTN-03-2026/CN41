<?php

namespace Modules\Users\Http\Controllers;

use App\Events\ActivityLogsCleared;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);

        $logs = Activity::with('causer')
            ->latest()
            ->paginate($perPage);

        $logs->getCollection()->transform(function ($log) {
            return [
                'id' => $log->id,
                'log_name' => $log->log_name,
                'description' => $log->description,
                'subject_type' => class_basename($log->subject_type),
                'subject_id' => $log->subject_id,
                'causer_name' => $log->causer ? $log->causer->name : 'Hệ thống',
                'properties' => $log->properties,
                'created_at' => $log->created_at->toDateTimeString(),
                'human_time' => $log->created_at->diffForHumans(),
            ];
        });

        return $this->paginated($logs, 'Tải lịch sử hoạt động thành công.');
    }

    public function clear(): JsonResponse
    {
        $admin = auth('admin')->user();
        Activity::truncate();

        event(new ActivityLogsCleared($admin));

        return $this->success(null, 'Đã dọn dẹp lịch sử hoạt động.');
    }
}

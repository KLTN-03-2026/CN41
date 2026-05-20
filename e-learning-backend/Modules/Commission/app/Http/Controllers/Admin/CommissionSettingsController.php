<?php

namespace Modules\Commission\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Commission\Models\CommissionSetting;

class CommissionSettingsController extends Controller
{
    use ApiResponse;

    public function show(): JsonResponse
    {
        return $this->success(CommissionSetting::current());
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'teacher_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $settings = CommissionSetting::current();
        $settings->update($validated);

        return $this->success($settings, 'Cài đặt hoa hồng đã được cập nhật.');
    }
}

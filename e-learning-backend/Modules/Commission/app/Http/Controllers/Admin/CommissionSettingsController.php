<?php

namespace Modules\Commission\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Commission\Http\Requests\UpdateCommissionSettingRequest;
use Modules\Commission\Models\CommissionSetting;

class CommissionSettingsController extends Controller
{
    use ApiResponse;

    public function show(): JsonResponse
    {
        return $this->success(CommissionSetting::current());
    }

    public function update(UpdateCommissionSettingRequest $request): JsonResponse
    {
        $settings = CommissionSetting::current();
        $settings->update($request->validated());

        return $this->success($settings, 'Cài đặt hoa hồng đã được cập nhật.');
    }
}

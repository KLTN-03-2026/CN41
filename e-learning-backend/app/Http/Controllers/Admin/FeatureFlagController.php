<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;

class FeatureFlagController extends Controller
{
    use ApiResponse;

    private const FLAGS = [
        'ai-quiz' => [
            'label' => 'AI Quiz Generation',
            'description' => 'Sinh câu hỏi trắc nghiệm tự động từ PDF bằng Gemini AI',
        ],
        'hls-transcoding' => [
            'label' => 'HLS Transcoding',
            'description' => 'Chuyển đổi video sang định dạng HLS sau khi upload',
        ],
        'payout-requests' => [
            'label' => 'Yêu cầu rút tiền',
            'description' => 'Cho phép giảng viên gửi và admin xử lý yêu cầu rút tiền',
        ],
    ];

    public function index(): JsonResponse
    {
        $data = collect(self::FLAGS)->map(fn ($meta, $key) => [
            'key' => $key,
            'label' => $meta['label'],
            'description' => $meta['description'],
            'active' => Feature::active($key),
        ])->values();

        return $this->success($data, 'Danh sách tính năng hệ thống');
    }

    public function update(Request $request, string $flag): JsonResponse
    {
        $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        if (! array_key_exists($flag, self::FLAGS)) {
            return $this->error('Flag không hợp lệ.', 422);
        }

        if ($request->boolean('active')) {
            Feature::activate($flag);
        } else {
            Feature::deactivate($flag);
        }

        return $this->success([
            'key' => $flag,
            'active' => Feature::active($flag),
        ], 'Cập nhật tính năng thành công');
    }
}

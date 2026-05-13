<?php

namespace Modules\Coupons\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Coupons\Http\Requests\BulkDeleteCouponsRequest;
use Modules\Coupons\Http\Requests\BulkRestoreCouponsRequest;
use Modules\Coupons\Http\Requests\IndexCouponRequest;
use Modules\Coupons\Http\Requests\StoreCouponRequest;
use Modules\Coupons\Http\Requests\UpdateCouponRequest;
use Modules\Coupons\Http\Requests\ValidateCouponRequest;
use Modules\Coupons\Http\Resources\CouponResource;
use Modules\Coupons\Repositories\CouponRepositoryInterface;

class CouponsController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CouponRepositoryInterface $repository,
    ) {}

    public function index(IndexCouponRequest $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['search', 'status', 'type']);

        $data = $this->repository->getFiltered($filters, $perPage);
        $data->setCollection(CouponResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        $coupon = $this->repository->create($request->validated());
        $coupon->refresh();

        return $this->success(new CouponResource($coupon), 'Mã giảm giá đã được tạo thành công.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $coupon = $this->repository->findOrFail($id);

        return $this->success(new CouponResource($coupon));
    }

    public function update(UpdateCouponRequest $request, int $id): JsonResponse
    {
        $coupon = $this->repository->update($id, $request->validated());

        return $this->success(new CouponResource($coupon), 'Mã giảm giá đã được cập nhật thành công.');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->success(null, 'Mã giảm giá đã được xoá thành công.');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $coupon = $this->repository->toggleStatus($id);
        $statusText = $coupon->status === 1 ? 'kích hoạt' : 'vô hiệu hoá';

        return $this->success(new CouponResource($coupon), "Mã giảm giá đã được {$statusText}.");
    }

    public function trashed(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $data = $this->repository->paginateTrashed($perPage);
        $data->setCollection(CouponResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    public function restore(int $id): JsonResponse
    {
        $this->repository->restore($id);

        return $this->success(null, 'Mã giảm giá đã được khôi phục thành công.');
    }

    public function forceDelete(int $id): JsonResponse
    {
        $this->repository->forceDeleteById($id);

        return $this->success(null, 'Mã giảm giá đã bị xoá vĩnh viễn.');
    }

    public function bulkDelete(BulkDeleteCouponsRequest $request): JsonResponse
    {
        $deleted = DB::transaction(function () use ($request) {
            return $this->repository->deleteMany($request->ids);
        });

        return $this->success(
            ['deleted_count' => $deleted, 'deleted_ids' => $request->ids],
            "Đã xoá {$deleted} mã giảm giá thành công."
        );
    }

    public function bulkRestore(BulkRestoreCouponsRequest $request): JsonResponse
    {
        $restored = DB::transaction(function () use ($request) {
            return $this->repository->restoreMany($request->ids);
        });

        return $this->success(
            ['restored_count' => $restored, 'restored_ids' => $request->ids],
            "Đã khôi phục {$restored} mã giảm giá thành công."
        );
    }

    public function listAvailable(): JsonResponse
    {
        $coupons = $this->repository->getAvailable();

        return $this->success(CouponResource::collection($coupons), 'Danh sách mã giảm giá có sẵn.');
    }

    public function validateCoupon(ValidateCouponRequest $request): JsonResponse
    {
        $code = strtoupper(trim($request->code));
        $subtotal = (float) $request->subtotal;

        $coupon = $this->repository->findByCode($code);

        if (! $coupon) {
            return $this->error('Mã giảm giá không tồn tại.', 422);
        }

        if (! $coupon->isValid()) {
            if ($coupon->end_date && $coupon->end_date->isPast()) {
                return $this->error('Mã giảm giá đã hết hạn.', 422);
            }
            if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
                return $this->error('Mã giảm giá đã hết lượt sử dụng.', 422);
            }

            return $this->error('Mã giảm giá không hợp lệ.', 422);
        }

        if ($coupon->min_order_value && $subtotal < (float) $coupon->min_order_value) {
            return $this->error(
                'Đơn hàng tối thiểu '.number_format($coupon->min_order_value).'₫ để sử dụng mã này.',
                422
            );
        }

        $discount = $coupon->calculateDiscount($subtotal);

        return $this->success([
            'valid' => true,
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'discount_amount' => round($discount, 2),
            'new_total' => round($subtotal - $discount, 2),
            'message' => $coupon->type === 'fixed'
                ? 'Giảm '.number_format($coupon->value).'₫'
                : "Giảm {$coupon->value}%".($coupon->max_discount ? ' (tối đa '.number_format($coupon->max_discount).'₫)' : ''),
        ], 'Mã giảm giá hợp lệ!');
    }
}

<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Interface RepositoryInterface
 *
 * Contract chuẩn cho tất cả Repository trong hệ thống.
 * Mọi module (Users, Students, Courses...) đều phải implement interface này,
 * đảm bảo tính nhất quán trong cách thao tác với dữ liệu.
 */
interface RepositoryInterface
{
    /**
     * Lấy tất cả bản ghi.
     *
     * @param  array  $columns  Các cột cần lấy
     * @param  array  $relations  Các quan hệ cần eager load
     */
    public function getAll(array $columns = ['*'], array $relations = []): Collection;

    /**
     * Tìm một bản ghi theo ID, trả null nếu không tìm thấy.
     *
     * @param  array  $columns  Các cột cần lấy
     * @param  array  $relations  Các quan hệ cần eager load
     */
    public function find(int $id, array $columns = ['*'], array $relations = []): ?Model;

    /**
     * Tìm một bản ghi theo ID, throw ModelNotFoundException nếu không tìm thấy.
     * Controller dùng method này thay vì find() + check null.
     *
     * @param  array  $columns  Các cột cần lấy
     * @param  array  $relations  Các quan hệ cần eager load
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail(int $id, array $columns = ['*'], array $relations = []): Model;

    /**
     * Tạo mới một bản ghi.
     *
     * @param  array  $data  Dữ liệu để tạo
     */
    public function create(array $data): Model;

    /**
     * Cập nhật một bản ghi theo ID.
     *
     * @param  array  $data  Dữ liệu cần cập nhật
     *
     * @throws ModelNotFoundException
     */
    public function update(int $id, array $data): Model;

    /**
     * Xoá một bản ghi theo ID.
     * Hỗ trợ cả hard delete và soft delete (tuỳ Model có dùng SoftDeletes hay không).
     *
     *
     * @throws ModelNotFoundException
     */
    public function delete(int $id): bool;

    /**
     * Xoá nhiều bản ghi theo mảng IDs.
     *
     * @param  array  $ids  Mảng ID cần xoá
     * @return int Số bản ghi đã xoá
     */
    public function deleteMany(array $ids): int;

    /**
     * Xoá vĩnh viễn (force delete) một bản ghi theo ID.
     * Chỉ áp dụng cho Model dùng SoftDeletes.
     *
     *
     * @throws ModelNotFoundException
     */
    public function forceDeleteById(int $id): bool;

    /**
     * Xoá vĩnh viễn (force delete) nhiều bản ghi theo mảng IDs.
     * Chỉ áp dụng cho Model dùng SoftDeletes.
     *
     * @return int Số bản ghi đã xoá vĩnh viễn
     */
    public function forceDeleteMany(array $ids): int;

    /**
     * Lấy danh sách bản ghi đã bị soft-delete (phân trang).
     */
    public function paginateTrashed(int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator;

    /**
     * Khôi phục (restore) một bản ghi đã soft-delete theo ID.
     *
     *
     * @throws ModelNotFoundException
     */
    public function restore(int $id): bool;

    /**
     * Khôi phục (restore) nhiều bản ghi đã soft-delete theo mảng IDs.
     *
     * @return int Số bản ghi đã khôi phục
     */
    public function restoreMany(array $ids): int;

    /**
     * Thực hiện hành động hàng loạt trên nhiều bản ghi.
     * Ví dụ: activate, deactivate, publish, archive...
     *
     * Action phải nằm trong whitelist — unknown action sẽ throw InvalidArgumentException.
     * Module con muốn thêm action → override method này trong Repository của module.
     *
     * @param  array  $ids  Mảng ID cần thao tác
     * @param  string  $action  Tên hành động (phải nằm trong whitelist)
     * @return int Số bản ghi bị ảnh hưởng
     *
     * @throws \InvalidArgumentException Khi action không được hỗ trợ
     */
    public function actionMany(array $ids, string $action): int;

    /**
     * Phân trang danh sách bản ghi.
     * $perPage tự động clamp trong khoảng [1, 100].
     *
     * @param  int  $perPage  Số bản ghi mỗi trang (max 100)
     * @param  array  $columns  Các cột cần lấy
     * @param  array  $relations  Các quan hệ cần eager load
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator;
}

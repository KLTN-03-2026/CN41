<?php

namespace Modules\Users\Repositories;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface UsersRepositoryInterface
 *
 * Contract cho Users Repository.
 * Extends RepositoryInterface (9 methods chuẩn: getAll, find, findOrFail, create, update, delete, deleteMany, actionMany, paginate).
 * Thêm các method riêng cho Users tại đây.
 */
interface UsersRepositoryInterface extends RepositoryInterface
{
    /**
     * Gán role cho nhiều users cùng lúc.
     *
     * @param  array<int>  $ids
     * @return int Số lượng user được cập nhật
     */
    public function assignRoleMany(array $ids, string $role): int;

    /**
     * Lấy danh sách users kèm filter.
     *
     * @param  array|null  $allowedRoles  Nếu không null, chỉ trả về user có role trong danh sách này.
     */
    public function paginateFiltered(array $filters, int $perPage = 15, bool $trashed = false, ?array $allowedRoles = null): LengthAwarePaginator;
}

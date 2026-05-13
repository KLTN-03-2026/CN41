<?php

namespace Modules\Users\Repositories;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UsersRepositoryInterface extends RepositoryInterface
{
    /**
     * @param  array<int>  $ids
     * @return int Số lượng user được cập nhật
     */
    public function assignRoleMany(array $ids, string $role): int;

    /**
     * @param  array|null  $allowedRoles  Nếu không null, chỉ trả về user có role trong danh sách này.
     */
    public function paginateFiltered(array $filters, int $perPage = 15, bool $trashed = false, ?array $allowedRoles = null): LengthAwarePaginator;

    /**
     * Returns true if any user in $ids has the super-admin role.
     *
     * @param  array<int>  $ids
     */
    public function hasSuperAdmin(array $ids): bool;
}

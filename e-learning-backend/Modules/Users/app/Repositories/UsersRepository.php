<?php

namespace Modules\Users\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Users\Models\User;

/**
 * Class UsersRepository
 *
 * Eloquent implementation cho UsersRepositoryInterface.
 * Extends BaseRepository (đã có sẵn 9 methods chuẩn + clamp perPage, soft-delete support).
 * Thêm các method riêng cho Users tại đây.
 */
class UsersRepository extends BaseRepository implements UsersRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function assignRoleMany(array $ids, string $role): int
    {
        $users = $this->model->whereIn('id', $ids)->get();

        foreach ($users as $user) {
            $user->syncRoles([$role]);
        }

        return $users->count();
    }

    public function hasSuperAdmin(array $ids): bool
    {
        return $this->model->whereIn('id', $ids)
            ->whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))
            ->exists();
    }

    /**
     * Paginate + filter users.
     *
     * @param  array|null  $allowedRoles  Nếu không null, chỉ trả về user có role trong danh sách này.
     */
    public function paginateFiltered(array $filters, int $perPage = 15, bool $trashed = false, ?array $allowedRoles = null): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));
        $query = $this->model->newQuery();

        if ($trashed) {
            $query->onlyTrashed();
        }

        $query->with(['roles']);

        // Giới hạn chỉ thấy user thuộc các role được phép (ví dụ: student, teacher)
        if (! is_null($allowedRoles)) {
            $query->whereHas('roles', function ($q) use ($allowedRoles) {
                $q->whereIn('name', $allowedRoles);
            });
        }

        // Filter: search by name/email
        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', $search)
                    ->orWhere('email', 'LIKE', $search);
            });
        }

        // Filter: role (chỉ trong phạm vi allowedRoles nếu có)
        if (! empty($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        // Filter: status
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int) $filters['status']);
        }

        return $query->paginate($perPage);
    }
}

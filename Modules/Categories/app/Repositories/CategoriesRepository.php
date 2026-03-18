<?php

namespace Modules\Categories\Repositories;

use App\Repositories\BaseRepository;
use Modules\Categories\Models\Category;

/**
 * Class CategoriesRepository
 *
 * Eloquent implementation cho CategoriesRepositoryInterface.
 * Extends BaseRepository (đã có sẵn 9 methods chuẩn + clamp perPage, soft-delete support).
 * Thêm các method riêng cho Categories tại đây.
 */
class CategoriesRepository extends BaseRepository implements CategoriesRepositoryInterface
{
    public function __construct(Categories $model)
    {
        parent::__construct($model);
    }
}

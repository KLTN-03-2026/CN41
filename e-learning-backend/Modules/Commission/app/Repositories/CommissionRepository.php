<?php

namespace Modules\Commission\Repositories;

use App\Repositories\BaseRepository;
use Modules\Commission\Models\CommissionSetting;

class CommissionRepository extends BaseRepository implements CommissionRepositoryInterface
{
    public function __construct(CommissionSetting $model)
    {
        parent::__construct($model);
    }
}

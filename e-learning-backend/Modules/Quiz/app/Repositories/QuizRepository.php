<?php

namespace Modules\Quiz\Repositories;

use App\Repositories\BaseRepository;
use Modules\Quiz\Models\Quiz;

class QuizRepository extends BaseRepository implements QuizRepositoryInterface
{
    public function __construct(Quiz $model)
    {
        parent::__construct($model);
    }
}

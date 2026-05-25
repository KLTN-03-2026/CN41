<?php

use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use Modules\Auth\Providers\AuthServiceProvider;
use Modules\Categories\Providers\CategoriesServiceProvider;
use Modules\Coupons\Providers\CouponsServiceProvider;
use Modules\Course\Providers\CourseServiceProvider;
use Modules\Dashboard\Providers\DashboardServiceProvider;
use Modules\Lessons\Providers\LessonsServiceProvider;
use Modules\Payment\Providers\PaymentServiceProvider;
use Modules\Posts\Providers\PostsServiceProvider;
use Modules\Quiz\Providers\QuizServiceProvider;
use Modules\Students\Providers\StudentsServiceProvider;
use Modules\Teachers\Providers\TeachersServiceProvider;
use Modules\Upload\Providers\UploadServiceProvider;
use Modules\Users\Providers\UsersServiceProvider;

return [
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    AuthServiceProvider::class,
    CategoriesServiceProvider::class,
    CouponsServiceProvider::class,
    CourseServiceProvider::class,
    DashboardServiceProvider::class,
    LessonsServiceProvider::class,
    PaymentServiceProvider::class,
    PostsServiceProvider::class,
    QuizServiceProvider::class,
    StudentsServiceProvider::class,
    TeachersServiceProvider::class,
    UploadServiceProvider::class,
    UsersServiceProvider::class,
];

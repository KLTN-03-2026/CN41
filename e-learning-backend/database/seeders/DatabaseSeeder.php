<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Categories\Database\Seeders\CategoriesDatabaseSeeder;
use Modules\Commission\Database\Seeders\CommissionSettingSeeder;
use Modules\Coupons\Database\Seeders\CouponsDatabaseSeeder;
use Modules\Course\Database\Seeders\CourseDatabaseSeeder;
use Modules\Lessons\Database\Seeders\LessonDatabaseSeeder;
use Modules\Lessons\Database\Seeders\LessonProgressSeeder;
use Modules\Payment\Database\Seeders\OrderSeeder;
use Modules\Posts\Database\Seeders\PostsDatabaseSeeder;
use Modules\Quiz\Database\Seeders\QuizDatabaseSeeder;
use Modules\Students\Database\Seeders\StudentsDatabaseSeeder;
use Modules\Teachers\Database\Seeders\TeachersDatabaseSeeder;
use Modules\Upload\Database\Seeders\MediaFileSeeder;
use Modules\Users\Database\Seeders\AdminUserSeeder;
use Modules\Users\Database\Seeders\RolePermissionSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            // 1. Roles & permissions (must run first — teachers and admins depend on roles)
            RolePermissionSeeder::class,

            // 2. Admin users (super-admin, admin)
            AdminUserSeeder::class,

            // 3. Category tree (courses depend on categories)
            CategoriesDatabaseSeeder::class,

            // 4. Teachers + linked User accounts
            TeachersDatabaseSeeder::class,

            // 5. Media files (lessons reference video/document IDs)
            MediaFileSeeder::class,

            // 6. Courses (depend on teachers + categories)
            CourseDatabaseSeeder::class,

            // 7. Sections + Lessons (depend on courses + media files)
            LessonDatabaseSeeder::class,

            // 8. Quizzes (attach to document-type lessons; must run after LessonDatabaseSeeder)
            QuizDatabaseSeeder::class,

            // 9. Students (30 accounts; OrderSeeder depends on students)
            StudentsDatabaseSeeder::class,

            // 10. Orders + Transactions + Enrollments (150 orders, 12-month trend)
            //     This seeder replaces the old StudentEnrollmentSeeder.
            OrderSeeder::class,

            // 11. Coupons
            CouponsDatabaseSeeder::class,

            // 12. Lesson progress (depends on enrollments from OrderSeeder)
            LessonProgressSeeder::class,

            // 13. Blog posts
            PostsDatabaseSeeder::class,

            // 14. Commission settings (singleton — default 70% teacher rate)
            CommissionSettingSeeder::class,
        ]);
    }
}

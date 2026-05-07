<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Categories\Database\Seeders\CategoriesDatabaseSeeder;
use Modules\Course\Database\Seeders\CourseDatabaseSeeder;
use Modules\Lessons\Database\Seeders\LessonDatabaseSeeder;
use Modules\Payment\Database\Seeders\OrderSeeder;
use Modules\Posts\Database\Seeders\PostsDatabaseSeeder;
use Modules\Students\Database\Seeders\StudentEnrollmentSeeder;
use Modules\Teachers\Database\Seeders\TeachersDatabaseSeeder;
use Modules\Upload\Database\Seeders\MediaFileSeeder;
use Modules\Users\Database\Seeders\AdminUserSeeder;
use Modules\Users\Database\Seeders\RolePermissionSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // 1. Roles & Permissions (phải chạy trước)
            RolePermissionSeeder::class,

            // 2. Admin users
            AdminUserSeeder::class,

            // 3. Categories (cây danh mục)
            CategoriesDatabaseSeeder::class,

            // 4. Teachers
            TeachersDatabaseSeeder::class,

            // 5. Media files (video + documents)
            MediaFileSeeder::class,

            // 6. Courses (phụ thuộc teachers + categories)
            CourseDatabaseSeeder::class,

            // 7. Sections + Lessons (phụ thuộc courses + media)
            LessonDatabaseSeeder::class,

            // 8. Students + Enrollments
            StudentEnrollmentSeeder::class,

            // 9. Orders (đơn hàng mẫu cho Dashboard)
            OrderSeeder::class,

            // 10. Posts (bài viết + danh mục + tags)
            PostsDatabaseSeeder::class,
        ]);
    }
}

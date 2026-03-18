<?php

namespace Modules\Teachers\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Teachers\Models\Teachers;

class TeachersDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teachers = [
            [
                'name'        => 'Nguyễn Văn An',
                'slug'        => 'nguyen-van-an',
                'description' => 'Giảng viên lập trình Web với hơn 10 năm kinh nghiệm trong phát triển phần mềm. Chuyên gia về PHP, Laravel, và các công nghệ web hiện đại.',
                'exp'         => 10,
                'image'       => null,
                'status'      => 1,
            ],
            [
                'name'        => 'Trần Thị Bình',
                'slug'        => 'tran-thi-binh',
                'description' => 'Chuyên gia thiết kế UI/UX với 8 năm kinh nghiệm. Đã tham gia thiết kế giao diện cho nhiều dự án lớn tại Việt Nam và quốc tế.',
                'exp'         => 8,
                'image'       => null,
                'status'      => 1,
            ],
            [
                'name'        => 'Lê Minh Cường',
                'slug'        => 'le-minh-cuong',
                'description' => 'Giảng viên Digital Marketing với 6 năm kinh nghiệm. Chuyên về SEO, Google Ads, và Facebook Marketing.',
                'exp'         => 6,
                'image'       => null,
                'status'      => 1,
            ],
            [
                'name'        => 'Phạm Hồng Đức',
                'slug'        => 'pham-hong-duc',
                'description' => 'Lập trình viên Mobile với 7 năm kinh nghiệm. Chuyên về Flutter, React Native và phát triển ứng dụng đa nền tảng.',
                'exp'         => 7,
                'image'       => null,
                'status'      => 1,
            ],
            [
                'name'        => 'Hoàng Thị Em',
                'slug'        => 'hoang-thi-em',
                'description' => 'Giảng viên tiếng Anh với chứng chỉ IELTS 8.5, 5 năm kinh nghiệm giảng dạy trực tuyến.',
                'exp'         => 5,
                'image'       => null,
                'status'      => 1,
            ],
        ];

        foreach ($teachers as $teacher) {
            Teachers::create($teacher);
        }
    }
}

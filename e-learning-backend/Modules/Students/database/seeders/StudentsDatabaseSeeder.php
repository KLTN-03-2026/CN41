<?php

namespace Modules\Students\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Students\Models\Student;

class StudentsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $students = [
            // Demo accounts
            ['name' => 'Student Demo',       'email' => 'student@elearning.com',           'verified' => true,  'dob' => '1999-05-10'],
            ['name' => 'Student Unverified',  'email' => 'student-unverified@elearning.com', 'verified' => false, 'dob' => '2000-01-15'],
            // Regular students
            ['name' => 'Nguyễn Thị Mai',     'email' => 'mai.nguyen@gmail.com',   'verified' => true, 'dob' => '1998-03-12'],
            ['name' => 'Trần Văn Hùng',      'email' => 'hung.tran@gmail.com',    'verified' => true, 'dob' => '1997-07-24'],
            ['name' => 'Lê Thị Lan',         'email' => 'lan.le@gmail.com',       'verified' => true, 'dob' => '2000-11-05'],
            ['name' => 'Phạm Minh Tuấn',     'email' => 'tuan.pham@gmail.com',    'verified' => true, 'dob' => '1996-09-18'],
            ['name' => 'Hoàng Thị Thu',      'email' => 'thu.hoang@gmail.com',    'verified' => true, 'dob' => '2001-02-28'],
            ['name' => 'Vũ Quang Khải',      'email' => 'khai.vu@gmail.com',      'verified' => true, 'dob' => '1999-12-01'],
            ['name' => 'Đặng Thị Hoa',       'email' => 'hoa.dang@gmail.com',     'verified' => true, 'dob' => '1998-06-14'],
            ['name' => 'Bùi Văn Nam',        'email' => 'nam.bui@gmail.com',      'verified' => true, 'dob' => '2000-04-20'],
            ['name' => 'Ngô Thị Yến',        'email' => 'yen.ngo@gmail.com',      'verified' => true, 'dob' => '1997-08-30'],
            ['name' => 'Đinh Văn Toàn',      'email' => 'toan.dinh@gmail.com',    'verified' => true, 'dob' => '1995-01-07'],
            ['name' => 'Lý Thị Phương',      'email' => 'phuong.ly@gmail.com',    'verified' => true, 'dob' => '2001-10-16'],
            ['name' => 'Cao Minh Đức',       'email' => 'duc.cao@gmail.com',      'verified' => true, 'dob' => '1998-05-22'],
            ['name' => 'Trịnh Thị Hằng',     'email' => 'hang.trinh@gmail.com',   'verified' => true, 'dob' => '1999-03-08'],
            ['name' => 'Phan Văn Lực',       'email' => 'luc.phan@gmail.com',     'verified' => true, 'dob' => '1996-11-13'],
            ['name' => 'Đỗ Thị Thảo',        'email' => 'thao.do@gmail.com',      'verified' => true, 'dob' => '2002-07-25'],
            ['name' => 'Nguyễn Quốc Bảo',    'email' => 'bao.nguyen2@gmail.com',  'verified' => true, 'dob' => '1997-02-17'],
            ['name' => 'Lê Thị Nhi',         'email' => 'nhi.le@gmail.com',       'verified' => true, 'dob' => '2000-09-04'],
            ['name' => 'Phạm Thị Hương',     'email' => 'huong.pham@gmail.com',   'verified' => true, 'dob' => '1998-12-19'],
            ['name' => 'Hoàng Văn Kiên',     'email' => 'kien.hoang@gmail.com',   'verified' => true, 'dob' => '1995-06-03'],
            ['name' => 'Vũ Thị Linh',        'email' => 'linh.vu@gmail.com',      'verified' => true, 'dob' => '2001-04-11'],
            ['name' => 'Đặng Quang Long',    'email' => 'long.dang@gmail.com',    'verified' => true, 'dob' => '1997-10-29'],
            ['name' => 'Bùi Thị Thơm',       'email' => 'thom.bui@gmail.com',     'verified' => true, 'dob' => '1999-01-23'],
            ['name' => 'Ngô Văn Dũng',       'email' => 'dung.ngo@gmail.com',     'verified' => true, 'dob' => '1996-08-06'],
            ['name' => 'Đinh Thị Quỳnh',     'email' => 'quynh.dinh@gmail.com',   'verified' => true, 'dob' => '2000-03-15'],
            ['name' => 'Lý Văn Hải',         'email' => 'hai.ly@gmail.com',       'verified' => true, 'dob' => '1994-07-21'],
            ['name' => 'Cao Thị Ngọc',       'email' => 'ngoc.cao@gmail.com',     'verified' => true, 'dob' => '2001-11-09'],
            ['name' => 'Trịnh Văn Phúc',     'email' => 'phuc.trinh@gmail.com',   'verified' => true, 'dob' => '1998-02-26'],
            ['name' => 'Phan Thị Cẩm',       'email' => 'cam.phan@gmail.com',     'verified' => true, 'dob' => '1997-05-14'],
        ];

        foreach ($students as $data) {
            Student::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'date_of_birth' => $data['dob'],
                    'email_verified_at' => $data['verified'] ? now() : null,
                ]
            );
        }

        $this->command->info('StudentsDatabaseSeeder: Seeded '.count($students).' students.');
    }
}

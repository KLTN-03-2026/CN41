# Seed Data Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rewrite all database seeders so that a single `php artisan migrate:fresh --seed` produces demo-ready, internally consistent data covering orders (12 months), quizzes, coupons, lesson progress, and realistic posts.

**Architecture:** Each seeder is self-contained and listed in dependency order in `DatabaseSeeder`. Orders drive enrollment (paid order → `students_course` row); no separate random enrollment seeder. All data is hardcoded for stable, reproducible output.

**Tech Stack:** Laravel 12, Nwidart Modules, Eloquent, Carbon, Spatie Permission

---

## File Map

| File | Action |
|------|--------|
| `database/seeders/DatabaseSeeder.php` | Modify — new order, remove StudentEnrollmentSeeder, add Quiz+Progress |
| `Modules/Categories/database/seeders/CategoriesDatabaseSeeder.php` | Modify — add `ky-nang-mem` + `thiet-ke-do-hoa` |
| `Modules/Course/database/seeders/CourseDatabaseSeeder.php` | Modify — deterministic teacher, fix category slugs |
| `Modules/Students/database/seeders/StudentsDatabaseSeeder.php` | Rewrite — 30 students |
| `Modules/Payment/database/seeders/OrderSeeder.php` | Rewrite — 150 orders + transactions + enrollments |
| `Modules/Coupons/database/seeders/CouponsDatabaseSeeder.php` | Rewrite — 6 coupons |
| `Modules/Quiz/database/seeders/QuizDatabaseSeeder.php` | **Create** — 3 quizzes × 5 questions |
| `Modules/Lessons/database/seeders/LessonProgressSeeder.php` | **Create** — progress for enrolled students |
| `Modules/Posts/database/seeders/PostsDatabaseSeeder.php` | Rewrite — real titles + content |

---

## Task 1: Fix CategoriesDatabaseSeeder — add two missing root categories

**Files:**
- Modify: `Modules/Categories/database/seeders/CategoriesDatabaseSeeder.php`

The existing seeder inserts 20 nodes then calls `Category::fixTree()`. Three courses reference `ky-nang-mem` and `thiet-ke-do-hoa` which don't exist — add them to the `$nodes` array before the `fixTree()` call.

- [ ] **Step 1: Add two nodes to the `$nodes` array**

Open `Modules/Categories/database/seeders/CategoriesDatabaseSeeder.php`. In the `$nodes` array, add these two entries **before the closing bracket**:

```php
            ['Kỹ năng mềm',     'ky-nang-mem',    'Phát triển bản thân và kỹ năng mềm', 'fa-star',       null],
            ['Thiết kế đồ họa', 'thiet-ke-do-hoa', 'Graphic design và đa phương tiện',   'fa-paint-brush', null],
```

The `$nodes` array should now have 22 entries total. No other changes needed in this file — `fixTree()` already runs after all inserts.

- [ ] **Step 2: Verify count after seeder runs (defer to Task 10 full run)**

Expected: `Category::count()` returns 22.

- [ ] **Step 3: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add Modules/Categories/database/seeders/CategoriesDatabaseSeeder.php && git commit -m 'fix(categories): add missing ky-nang-mem and thiet-ke-do-hoa seed categories'" | cat
```

---

## Task 2: Fix CourseDatabaseSeeder — deterministic teacher + category slugs

**Files:**
- Modify: `Modules/Course/database/seeders/CourseDatabaseSeeder.php`

Two bugs: (1) teachers assigned via `array_rand` — every seed produces different assignments. (2) 3 courses reference categories that didn't exist before Task 1.

- [ ] **Step 1: Replace the `run()` method teacher-lookup logic**

Replace the lines that build `$teachers` (array of ids from random pluck) with a slug→id map:

```php
        // Build slug→id map for deterministic teacher assignment
        $teacherMap = Teachers::pluck('id', 'slug')->toArray();
```

Remove the old line:
```php
        $teachers = Teachers::where('status', 1)->pluck('id')->toArray();
```

- [ ] **Step 2: Replace teacher assignment in the foreach loop**

Replace:
```php
                'teacher_id' => $teachers[array_rand($teachers)],
```
With:
```php
                'teacher_id' => $teacherMap[$data['teacher_slug']] ?? $teacherMap[array_key_first($teacherMap)],
```

- [ ] **Step 3: Add `teacher_slug` key to every course in `courseData()`**

Replace the full `courseData()` method with the version below (only `teacher_slug` is added; all other fields unchanged):

```php
    private function courseData(): array
    {
        return [
            // ── Web / Backend — Nguyễn Văn An ──
            [
                'name' => 'Laravel 12 Từ Cơ Bản Đến Nâng Cao',
                'description' => 'Khóa học Laravel toàn diện cho lập trình viên PHP. Bạn sẽ học routing, Eloquent ORM, Sanctum API, Queue, Job, Event, Testing và deploy thực tế. Phù hợp từ người mới bắt đầu đến developer muốn nâng cao.',
                'price' => 599000, 'sale_price' => 399000, 'level' => 'beginner', 'status' => 1,
                'category_slugs' => ['laravel', 'web-development'],
                'teacher_slug' => 'nguyen-van-an',
            ],
            [
                'name' => 'Vue.js 3 & Pinia Thực Chiến',
                'description' => 'Học Vue.js 3 với Composition API, Pinia state management, Vue Router và Tailwind CSS. Xây dựng SPA hoàn chỉnh tích hợp REST API. Thực hành qua dự án thực tế từ đầu đến cuối.',
                'price' => 499000, 'sale_price' => null, 'level' => 'intermediate', 'status' => 1,
                'category_slugs' => ['vuejs', 'web-development'],
                'teacher_slug' => 'nguyen-van-an',
            ],
            [
                'name' => 'HTML, CSS & JavaScript Cho Người Mới',
                'description' => 'Khóa học nền tảng web development hoàn toàn miễn phí. Bạn sẽ nắm vững HTML5, CSS3 với Flexbox và Grid layout, JavaScript ES6+ và DOM manipulation. Điểm khởi đầu lý tưởng cho mọi lập trình viên web.',
                'price' => 0, 'sale_price' => null, 'level' => 'beginner', 'status' => 1,
                'category_slugs' => ['html-css', 'javascript', 'web-development'],
                'teacher_slug' => 'nguyen-van-an',
            ],
            [
                'name' => 'React.js & Next.js Full-Stack',
                'description' => 'Xây dựng ứng dụng full-stack hiện đại với React.js, Next.js 14 App Router và Node.js. Học SSR, SSG, API Routes, authentication, và deploy lên Vercel. Dự án thực tế: E-Commerce Platform.',
                'price' => 799000, 'sale_price' => 599000, 'level' => 'intermediate', 'status' => 1,
                'category_slugs' => ['react', 'web-development'],
                'teacher_slug' => 'nguyen-van-an',
            ],
            [
                'name' => 'Node.js & Express REST API',
                'description' => 'Xây dựng REST API production-ready với Node.js, Express, JWT authentication, MongoDB và Redis cache. Áp dụng clean architecture, middleware pattern và unit testing.',
                'price' => 450000, 'sale_price' => null, 'level' => 'intermediate', 'status' => 1,
                'category_slugs' => ['nodejs', 'web-development'],
                'teacher_slug' => 'nguyen-van-an',
            ],
            [
                'name' => 'API Design & RESTful Best Practices',
                'description' => 'Thiết kế REST API chuẩn: naming conventions, versioning, authentication (JWT/OAuth2), rate limiting, pagination và documentation với Swagger/OpenAPI. Kèm ví dụ thực tế với Laravel và Node.js.',
                'price' => 399000, 'sale_price' => null, 'level' => 'intermediate', 'status' => 1,
                'category_slugs' => ['web-development'],
                'teacher_slug' => 'nguyen-van-an',
            ],
            [
                'name' => 'TypeScript Từ Cơ Bản Đến Nâng Cao',
                'description' => 'Học TypeScript toàn diện: kiểu dữ liệu tĩnh, interface, generics, decorators và tích hợp với React, Node.js. Nâng cao chất lượng code và giảm lỗi runtime trong dự án thực tế.',
                'price' => 449000, 'sale_price' => 299000, 'level' => 'intermediate', 'status' => 1,
                'category_slugs' => ['javascript', 'web-development'],
                'teacher_slug' => 'nguyen-van-an',
            ],
            [
                'name' => 'Spring Boot & Java Backend',
                'description' => 'Xây dựng backend enterprise với Spring Boot, Spring Security, JPA/Hibernate và MySQL. Triển khai REST API, JWT auth, microservices cơ bản và Docker.',
                'price' => 699000, 'sale_price' => 499000, 'level' => 'intermediate', 'status' => 1,
                'category_slugs' => ['web-development'],
                'teacher_slug' => 'nguyen-van-an',
            ],
            [
                'name' => 'Git & GitHub Cho Lập Trình Viên',
                'description' => 'Nắm vững Git workflow: branching, merging, rebasing, conflict resolution, pull request và code review. Tích hợp CI/CD cơ bản với GitHub Actions. Kỹ năng thiết yếu cho mọi developer.',
                'price' => 199000, 'sale_price' => null, 'level' => 'beginner', 'status' => 1,
                'category_slugs' => ['devops-cloud'],
                'teacher_slug' => 'nguyen-van-an',
            ],

            // ── Database — Đặng Thị Giang ──
            [
                'name' => 'MySQL Nâng Cao & Tối Ưu Query',
                'description' => 'Nắm vững thiết kế database chuẩn, normalization, indexing chiến lược, query optimization và stored procedures. Thực hành với MySQL 8.0 trên dữ liệu triệu record thực tế.',
                'price' => 349000, 'sale_price' => 249000, 'level' => 'advanced', 'status' => 1,
                'category_slugs' => ['co-so-du-lieu'],
                'teacher_slug' => 'dang-thi-giang',
            ],
            [
                'name' => 'PostgreSQL & Thiết Kế Database',
                'description' => 'Học PostgreSQL từ cơ bản đến nâng cao: DDL/DML, joins, indexing, partitioning, full-text search và JSON support. Thiết kế schema chuẩn cho hệ thống lớn.',
                'price' => 399000, 'sale_price' => 299000, 'level' => 'intermediate', 'status' => 1,
                'category_slugs' => ['co-so-du-lieu'],
                'teacher_slug' => 'dang-thi-giang',
            ],

            // ── Mobile — Phạm Hồng Đức ──
            [
                'name' => 'Flutter & Dart Từ Zero Đến Hero',
                'description' => 'Học Flutter và Dart để xây dựng ứng dụng đa nền tảng iOS & Android. Bao gồm state management với Bloc/Provider, kết nối API, local storage và publish lên store.',
                'price' => 699000, 'sale_price' => 499000, 'level' => 'beginner', 'status' => 1,
                'category_slugs' => ['flutter', 'mobile-development'],
                'teacher_slug' => 'pham-hong-duc',
            ],
            [
                'name' => 'React Native Thực Chiến 2025',
                'description' => 'Phát triển app mobile cross-platform với React Native và Expo. Học navigation, camera, push notification, payment integration và CI/CD cho mobile.',
                'price' => 599000, 'sale_price' => null, 'level' => 'intermediate', 'status' => 1,
                'category_slugs' => ['react-native', 'mobile-development'],
                'teacher_slug' => 'pham-hong-duc',
            ],
            [
                'name' => 'iOS Development với Swift & SwiftUI',
                'description' => 'Phát triển ứng dụng iOS native với Swift và SwiftUI. Học data binding, navigation, networking, CoreData, notifications và submit lên App Store.',
                'price' => 749000, 'sale_price' => null, 'level' => 'intermediate', 'status' => 1,
                'category_slugs' => ['mobile-development'],
                'teacher_slug' => 'pham-hong-duc',
            ],

            // ── DevOps — Vũ Đình Phú ──
            [
                'name' => 'Docker & DevOps Cho Developer',
                'description' => 'Học Docker, Docker Compose, CI/CD với GitHub Actions và GitLab CI. Deploy ứng dụng lên VPS với Nginx reverse proxy, SSL và monitoring. Nền tảng DevOps thiết yếu cho mọi developer.',
                'price' => 450000, 'sale_price' => 350000, 'level' => 'advanced', 'status' => 1,
                'category_slugs' => ['devops-cloud'],
                'teacher_slug' => 'vu-dinh-phu',
            ],
            [
                'name' => 'Kubernetes & Container Orchestration',
                'description' => 'Triển khai và quản lý ứng dụng container hóa với Kubernetes: Pod, Deployment, Service, Ingress, ConfigMap, Secret và Helm chart. Thực hành trên cluster thực tế.',
                'price' => 799000, 'sale_price' => 599000, 'level' => 'advanced', 'status' => 1,
                'category_slugs' => ['devops-cloud'],
                'teacher_slug' => 'vu-dinh-phu',
            ],

            // ── AI / Data — Đặng Thị Giang ──
            [
                'name' => 'Python & Machine Learning Cơ Bản',
                'description' => 'Nhập môn Python và Machine Learning. Sử dụng NumPy, Pandas, Matplotlib để phân tích dữ liệu. Xây dựng model với Scikit-learn và deploy API dự đoán đầu tiên của bạn.',
                'price' => 699000, 'sale_price' => 499000, 'level' => 'beginner', 'status' => 1,
                'category_slugs' => ['python', 'machine-learning', 'data-science'],
                'teacher_slug' => 'dang-thi-giang',
            ],
            [
                'name' => 'Deep Learning & Neural Networks',
                'description' => 'Xây dựng mạng neural từ đầu với Python và TensorFlow/Keras. Học CNN, RNN, LSTM, Transformer và ứng dụng trong nhận diện ảnh, xử lý ngôn ngữ tự nhiên.',
                'price' => 899000, 'sale_price' => 699000, 'level' => 'advanced', 'status' => 1,
                'category_slugs' => ['machine-learning', 'data-science'],
                'teacher_slug' => 'dang-thi-giang',
            ],

            // ── Languages — Hoàng Thị Em ──
            [
                'name' => 'IELTS 7.0 Toàn Diện 4 Kỹ Năng',
                'description' => 'Lộ trình học IELTS bài bản từ band 5.0 lên 7.0+. Bao gồm Listening, Reading, Writing Task 1 & 2, Speaking. Kèm 500+ đề thi thử và bộ từ vựng học thuật IELTS chuyên sâu.',
                'price' => 899000, 'sale_price' => 699000, 'level' => 'intermediate', 'status' => 1,
                'category_slugs' => ['tieng-anh'],
                'teacher_slug' => 'hoang-thi-em',
            ],
            [
                'name' => 'Tiếng Anh Giao Tiếp Văn Phòng',
                'description' => 'Học tiếng Anh thực tế cho môi trường công sở: email chuyên nghiệp, meeting, thuyết trình và đàm phán. Tập trung phát âm chuẩn và phản xạ giao tiếp tự nhiên.',
                'price' => 399000, 'sale_price' => null, 'level' => 'beginner', 'status' => 1,
                'category_slugs' => ['tieng-anh'],
                'teacher_slug' => 'hoang-thi-em',
            ],
            [
                'name' => 'Tiếng Nhật N5-N4 Cho Người Mới Bắt Đầu',
                'description' => 'Học tiếng Nhật từ zero: Hiragana, Katakana, Kanji N5, ngữ pháp và hội thoại cơ bản. Luyện thi JLPT N5 và N4 với bộ đề thi thử đầy đủ và giải thích chi tiết.',
                'price' => 499000, 'sale_price' => 349000, 'level' => 'beginner', 'status' => 1,
                'category_slugs' => ['tieng-nhat'],
                'teacher_slug' => 'hoang-thi-em',
            ],
            [
                'name' => 'Tiếng Hàn TOPIK I - Sơ Cấp',
                'description' => 'Học tiếng Hàn từ bảng chữ cái Hangul đến TOPIK I level 1-2. Bao gồm phát âm chuẩn, từ vựng 1000 từ cơ bản, ngữ pháp và luyện đề thi TOPIK I thực tế.',
                'price' => 449000, 'sale_price' => null, 'level' => 'beginner', 'status' => 1,
                'category_slugs' => ['tieng-han'],
                'teacher_slug' => 'hoang-thi-em',
            ],

            // ── Soft Skills — Lê Minh Cường ──
            [
                'name' => 'Kỹ Năng Thuyết Trình & Public Speaking',
                'description' => 'Xây dựng kỹ năng thuyết trình chuyên nghiệp: cấu trúc bài nói, ngôn ngữ cơ thể, xử lý câu hỏi và vượt qua nỗi sợ đám đông. Thực hành qua video feedback.',
                'price' => 299000, 'sale_price' => 199000, 'level' => 'beginner', 'status' => 1,
                'category_slugs' => ['ky-nang-mem'],
                'teacher_slug' => 'le-minh-cuong',
            ],

            // ── Design — Trần Thị Bình ──
            [
                'name' => 'Photoshop & Thiết Kế Đồ Họa Cơ Bản',
                'description' => 'Học Adobe Photoshop từ zero: công cụ cơ bản, chỉnh sửa ảnh, thiết kế banner, poster và mockup sản phẩm. Phù hợp cho người mới muốn học thiết kế.',
                'price' => 349000, 'sale_price' => null, 'level' => 'beginner', 'status' => 0,
                'category_slugs' => ['thiet-ke-do-hoa'],
                'teacher_slug' => 'tran-thi-binh',
            ],
            [
                'name' => 'UI/UX Design với Figma',
                'description' => 'Thiết kế giao diện và trải nghiệm người dùng với Figma: wireframe, prototype, design system và handoff cho developer. Xây dựng portfolio design chuyên nghiệp.',
                'price' => 499000, 'sale_price' => 349000, 'level' => 'intermediate', 'status' => 1,
                'category_slugs' => ['thiet-ke-do-hoa'],
                'teacher_slug' => 'tran-thi-binh',
            ],
        ];
    }
```

- [ ] **Step 4: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add Modules/Course/database/seeders/CourseDatabaseSeeder.php && git commit -m 'fix(course): deterministic teacher assignment and fix missing category slugs'" | cat
```

---

## Task 3: Rewrite StudentsDatabaseSeeder — 30 students

**Files:**
- Modify: `Modules/Students/database/seeders/StudentsDatabaseSeeder.php`

- [ ] **Step 1: Replace the entire file content**

```php
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
                    'name'              => $data['name'],
                    'password'          => Hash::make('password'),
                    'date_of_birth'     => $data['dob'],
                    'email_verified_at' => $data['verified'] ? now() : null,
                ]
            );
        }

        $this->command->info('StudentsDatabaseSeeder: Seeded ' . count($students) . ' students.');
    }
}
```

- [ ] **Step 2: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add Modules/Students/database/seeders/StudentsDatabaseSeeder.php && git commit -m 'feat(students): rewrite seeder with 30 realistic students'" | cat
```

---

## Task 4: Rewrite OrderSeeder — 150 orders + transactions + enrollments

**Files:**
- Modify: `Modules/Payment/database/seeders/OrderSeeder.php`

This is the most complex seeder. It replaces `StudentEnrollmentSeeder` entirely — enrollment is created only from paid orders. Free courses get enrolled directly.

- [ ] **Step 1: Replace the entire file content**

```php
<?php

namespace Modules\Payment\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Course\Models\Course;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\OrderItem;
use Modules\Payment\Models\Transaction;
use Modules\Students\Models\Student;

class OrderSeeder extends Seeder
{
    // Track enrolled (student_id → [course_ids]) to prevent duplicates
    private array $enrolled = [];

    public function run(): void
    {
        $students = Student::where('email_verified_at', '!=', null)->get();

        if ($students->isEmpty()) {
            $this->command->warn('OrderSeeder: No verified students found. Run StudentsDatabaseSeeder first.');
            return;
        }

        $paidCourses = Course::where('status', 1)->where('price', '>', 0)->get(['id', 'price', 'sale_price']);
        $freeCourses = Course::where('status', 1)->where('price', 0)->get(['id', 'price', 'sale_price']);

        if ($paidCourses->isEmpty()) {
            $this->command->warn('OrderSeeder: No paid courses found.');
            return;
        }

        // Enroll all verified students in free courses directly (no order needed)
        foreach ($students as $student) {
            foreach ($freeCourses as $course) {
                $this->enroll($student->id, $course->id, now()->subDays(180));
            }
        }

        // Monthly order distribution: 12 months ending May 2026
        $monthlyPlan = [
            [2025, 6,  6],
            [2025, 7,  8],
            [2025, 8,  9],
            [2025, 9,  10],
            [2025, 10, 11],
            [2025, 11, 13],
            [2025, 12, 15],
            [2026, 1,  12],
            [2026, 2,  13],
            [2026, 3,  15],
            [2026, 4,  18],
            [2026, 5,  20],
        ];

        $bankCodes = ['NCB', 'VIETCOMBANK', 'TECHCOMBANK', 'MBBANK', 'VCB'];
        $studentIds = $students->pluck('id')->toArray();

        foreach ($monthlyPlan as [$year, $month, $count]) {
            $monthStart = Carbon::create($year, $month, 1, 0, 0, 0);
            $monthEnd   = $monthStart->copy()->endOfMonth()->setTime(23, 59, 59);

            for ($i = 0; $i < $count; $i++) {
                $studentId = $studentIds[array_rand($studentIds)];
                $status    = $this->randomStatus();
                $gateway   = rand(1, 10) <= 7 ? 'vnpay' : 'zalopay';

                // Random timestamp within month
                $createdAt = Carbon::createFromTimestamp(
                    rand($monthStart->timestamp, $monthEnd->timestamp)
                );
                $paidAt = $status === 'paid'
                    ? $createdAt->copy()->addMinutes(rand(1, 15))
                    : null;

                // Pick 1–3 courses this student doesn't own yet
                $ownedIds = $this->enrolled[$studentId] ?? [];
                $available = $paidCourses->reject(fn ($c) => in_array($c->id, $ownedIds))->values();

                if ($available->isEmpty()) {
                    continue; // student owns everything, skip
                }

                $numCourses = min(rand(1, 3), $available->count());
                $selected   = $available->random($numCourses);

                $subtotal = $selected->sum(fn ($c) => $c->sale_price ?? $c->price);

                $order = Order::create([
                    'order_code'     => 'ORD-' . $createdAt->format('Ymd') . '-' . strtoupper(Str::random(5)),
                    'student_id'     => $studentId,
                    'subtotal'       => $subtotal,
                    'discount_amount' => 0,
                    'total_amount'   => $subtotal,
                    'status'         => $status,
                    'payment_method' => $gateway,
                    'paid_at'        => $paidAt,
                    'created_at'     => $createdAt,
                    'updated_at'     => $createdAt,
                ]);

                foreach ($selected as $course) {
                    OrderItem::create([
                        'order_id'    => $order->id,
                        'course_id'   => $course->id,
                        'price'       => $course->price,
                        'sale_price'  => $course->sale_price,
                        'final_price' => $course->sale_price ?? $course->price,
                        'created_at'  => $createdAt,
                        'updated_at'  => $createdAt,
                    ]);

                    if ($status === 'paid') {
                        $this->enroll($studentId, $course->id, $paidAt);
                        Course::where('id', $course->id)->increment('total_students');
                    }
                }

                if ($status === 'paid') {
                    Transaction::create([
                        'order_id'         => $order->id,
                        'gateway'          => $gateway,
                        'transaction_code' => strtoupper(Str::random(12)),
                        'bank_code'        => $gateway === 'vnpay' ? $bankCodes[array_rand($bankCodes)] : null,
                        'amount'           => $order->total_amount,
                        'status'           => 'success',
                        'paid_at'          => $paidAt,
                        'gateway_response' => ['seeded' => true],
                        'created_at'       => $createdAt,
                        'updated_at'       => $createdAt,
                    ]);
                }
            }
        }

        $paid    = Order::where('status', 'paid')->count();
        $total   = Order::count();
        $revenue = Order::where('status', 'paid')->sum('total_amount');
        $enrCount = DB::table('students_course')->count();
        $this->command->info("OrderSeeder: {$total} orders ({$paid} paid), revenue: " . number_format($revenue) . ' VNĐ, ' . $enrCount . ' enrollments.');
    }

    private function randomStatus(): string
    {
        $rand = rand(1, 100);
        return match (true) {
            $rand <= 70  => 'paid',
            $rand <= 85  => 'pending',
            $rand <= 95  => 'failed',
            default      => 'cancelled',
        };
    }

    private function enroll(int $studentId, int $courseId, ?Carbon $enrolledAt): void
    {
        if (in_array($courseId, $this->enrolled[$studentId] ?? [])) {
            return;
        }

        DB::table('students_course')->insertOrIgnore([
            'student_id'  => $studentId,
            'course_id'   => $courseId,
            'enrolled_at' => $enrolledAt ?? now(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->enrolled[$studentId][] = $courseId;
    }
}
```

- [ ] **Step 2: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add Modules/Payment/database/seeders/OrderSeeder.php && git commit -m 'feat(payment): rewrite OrderSeeder with 150 orders, transactions, and enrollment'" | cat
```

---

## Task 5: Rewrite CouponsDatabaseSeeder — 6 coupons

**Files:**
- Modify: `Modules/Coupons/database/seeders/CouponsDatabaseSeeder.php`

- [ ] **Step 1: Replace the entire file content**

```php
<?php

namespace Modules\Coupons\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CouponsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $coupons = [
            [
                'code'            => 'NEWUSER10',
                'type'            => 'percentage',
                'value'           => 10,
                'min_order_value' => 200000,
                'max_discount'    => 50000,
                'usage_limit'     => null,
                'used_count'      => 0,
                'start_date'      => '2026-01-01 00:00:00',
                'end_date'        => '2027-12-31 23:59:59',
                'status'          => 1,
                'description'     => 'Giảm 10% cho học viên mới (tối đa 50.000đ)',
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'code'            => 'FLASH50',
                'type'            => 'fixed',
                'value'           => 50000,
                'min_order_value' => 300000,
                'max_discount'    => null,
                'usage_limit'     => 100,
                'used_count'      => 37,
                'start_date'      => '2026-05-01 00:00:00',
                'end_date'        => '2026-06-30 23:59:59',
                'status'          => 1,
                'description'     => 'Giảm ngay 50.000đ cho đơn từ 300.000đ',
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'code'            => 'SUMMER30',
                'type'            => 'percentage',
                'value'           => 30,
                'min_order_value' => 500000,
                'max_discount'    => 150000,
                'usage_limit'     => 50,
                'used_count'      => 12,
                'start_date'      => '2026-06-01 00:00:00',
                'end_date'        => '2026-07-31 23:59:59',
                'status'          => 1,
                'description'     => 'Khuyến mãi hè: giảm 30% (tối đa 150.000đ)',
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'code'            => 'TECH200',
                'type'            => 'fixed',
                'value'           => 200000,
                'min_order_value' => 800000,
                'max_discount'    => null,
                'usage_limit'     => 30,
                'used_count'      => 8,
                'start_date'      => '2026-04-01 00:00:00',
                'end_date'        => '2026-05-31 23:59:59',
                'status'          => 1,
                'description'     => 'Giảm 200.000đ cho khóa học công nghệ từ 800.000đ',
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'code'            => 'EXPIRED2025',
                'type'            => 'percentage',
                'value'           => 20,
                'min_order_value' => 100000,
                'max_discount'    => null,
                'usage_limit'     => null,
                'used_count'      => 245,
                'start_date'      => '2025-01-01 00:00:00',
                'end_date'        => '2025-12-31 23:59:59',
                'status'          => 0,
                'description'     => 'Mã giảm giá năm 2025 (đã hết hạn)',
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'code'            => 'VIP500',
                'type'            => 'fixed',
                'value'           => 500000,
                'min_order_value' => 2000000,
                'max_discount'    => null,
                'usage_limit'     => 10,
                'used_count'      => 2,
                'start_date'      => '2026-01-01 00:00:00',
                'end_date'        => '2026-12-31 23:59:59',
                'status'          => 1,
                'description'     => 'Ưu đãi VIP: giảm 500.000đ cho đơn từ 2.000.000đ',
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
        ];

        foreach ($coupons as $coupon) {
            DB::table('coupons')->updateOrInsert(['code' => $coupon['code']], $coupon);
        }

        $this->command->info('CouponsDatabaseSeeder: Seeded ' . count($coupons) . ' coupons.');
    }
}
```

- [ ] **Step 2: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add Modules/Coupons/database/seeders/CouponsDatabaseSeeder.php && git commit -m 'feat(coupons): seed 6 demo coupons (percentage, fixed, expired, active)'" | cat
```

---

## Task 6: Create QuizDatabaseSeeder — 3 quizzes × 5 questions

**Files:**
- Create: `Modules/Quiz/database/seeders/QuizDatabaseSeeder.php`

Attaches a quiz to the first document-type lesson in the first section of three courses. Also updates those lessons' `type` to `'quiz'`.

- [ ] **Step 1: Create the file**

```php
<?php

namespace Modules\Quiz\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Course\Models\Course;
use Modules\Lessons\Models\Lesson;
use Modules\Lessons\Models\Section;
use Modules\Quiz\Models\Quiz;
use Modules\Quiz\Models\QuizQuestion;

class QuizDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $targets = $this->quizData();

        foreach ($targets as $target) {
            $course = Course::where('name', $target['course_name'])->first();
            if (! $course) {
                $this->command->warn("QuizDatabaseSeeder: Course not found — {$target['course_name']}");
                continue;
            }

            $firstSection = Section::where('course_id', $course->id)
                ->orderBy('order')
                ->first();
            if (! $firstSection) {
                continue;
            }

            // The 3rd lesson in any section (index 2) is always type=document per LessonDatabaseSeeder pattern
            $lesson = Lesson::where('section_id', $firstSection->id)
                ->where('type', 'document')
                ->orderBy('order')
                ->first();
            if (! $lesson) {
                continue;
            }

            // Mark lesson as quiz type
            $lesson->update(['type' => 'quiz']);

            $quiz = Quiz::create([
                'lesson_id'   => $lesson->id,
                'title'       => $target['title'],
                'description' => $target['description'],
                'max_attempts' => 3,
                'time_limit'  => 10,
                'status'      => 1,
            ]);

            foreach ($target['questions'] as $order => $q) {
                QuizQuestion::create([
                    'quiz_id'        => $quiz->id,
                    'question'       => $q['q'],
                    'option_a'       => $q['a'],
                    'option_b'       => $q['b'],
                    'option_c'       => $q['c'],
                    'option_d'       => $q['d'],
                    'correct_option' => $q['correct'],
                    'order'          => $order + 1,
                ]);
            }

            $this->command->info("QuizDatabaseSeeder: Created quiz '{$quiz->title}' with 5 questions.");
        }
    }

    private function quizData(): array
    {
        return [
            [
                'course_name' => 'Laravel 12 Từ Cơ Bản Đến Nâng Cao',
                'title'       => 'Kiểm tra kiến thức Laravel cơ bản',
                'description' => 'Bài kiểm tra sau khi hoàn thành Chương 1. Thời gian: 10 phút, 5 câu hỏi.',
                'questions'   => [
                    [
                        'q'       => 'Middleware trong Laravel có vai trò chính là gì?',
                        'a'       => 'Quản lý kết nối database',
                        'b'       => 'Lọc và xử lý HTTP request trước khi vào controller',
                        'c'       => 'Render Blade template',
                        'd'       => 'Gửi email thông báo',
                        'correct' => 'B',
                    ],
                    [
                        'q'       => 'Lệnh Artisan nào tạo một Controller mới?',
                        'a'       => 'php artisan make:model',
                        'b'       => 'php artisan new:controller',
                        'c'       => 'php artisan make:controller',
                        'd'       => 'php artisan create:controller',
                        'correct' => 'C',
                    ],
                    [
                        'q'       => 'Trong Eloquent, quan hệ hasMany diễn tả điều gì?',
                        'a'       => 'Một model thuộc về nhiều model khác',
                        'b'       => 'Quan hệ nhiều-nhiều',
                        'c'       => 'Một model có nhiều bản ghi liên kết ở bảng khác',
                        'd'       => 'Quan hệ một-một',
                        'correct' => 'C',
                    ],
                    [
                        'q'       => 'CSRF token trong Laravel bảo vệ chống lại tấn công nào?',
                        'a'       => 'SQL Injection',
                        'b'       => 'Cross-Site Request Forgery',
                        'c'       => 'XSS (Cross-Site Scripting)',
                        'd'       => 'Man-in-the-Middle',
                        'correct' => 'B',
                    ],
                    [
                        'q'       => 'Câu lệnh nào chạy tất cả migration chưa được thực thi?',
                        'a'       => 'php artisan db:seed',
                        'b'       => 'php artisan schema:up',
                        'c'       => 'php artisan migrate:fresh',
                        'd'       => 'php artisan migrate',
                        'correct' => 'D',
                    ],
                ],
            ],
            [
                'course_name' => 'Vue.js 3 & Pinia Thực Chiến',
                'title'       => 'Kiểm tra kiến thức Vue 3',
                'description' => 'Bài kiểm tra sau khi hoàn thành Chương 1. Thời gian: 10 phút, 5 câu hỏi.',
                'questions'   => [
                    [
                        'q'       => 'Cú pháp nào dùng để bind attribute một chiều trong Vue 3?',
                        'a'       => 'v-model',
                        'b'       => 'v-on:attr',
                        'c'       => ':attr hoặc v-bind:attr',
                        'd'       => 'v-if',
                        'correct' => 'C',
                    ],
                    [
                        'q'       => 'Pinia khác Vuex ở điểm quan trọng nào?',
                        'a'       => 'Pinia không hỗ trợ TypeScript',
                        'b'       => 'Pinia không cần mutations, trực tiếp thay đổi state trong actions',
                        'c'       => 'Pinia chỉ dùng được với Vue 2',
                        'd'       => 'Pinia chậm hơn Vuex',
                        'correct' => 'B',
                    ],
                    [
                        'q'       => 'reactive() trong Vue 3 khác ref() ở điểm nào?',
                        'a'       => 'reactive() dùng cho primitive values, ref() cho object',
                        'b'       => 'reactive() dùng cho object/array, ref() dùng được cho mọi kiểu dữ liệu',
                        'c'       => 'Không có sự khác biệt',
                        'd'       => 'reactive() đã lỗi thời và không nên dùng',
                        'correct' => 'B',
                    ],
                    [
                        'q'       => 'Directive v-for trong Vue dùng để làm gì?',
                        'a'       => 'Tạo điều kiện hiển thị phần tử',
                        'b'       => 'Lắng nghe DOM event',
                        'c'       => 'Lặp qua danh sách để render nhiều phần tử',
                        'd'       => 'Bind dynamic class',
                        'correct' => 'C',
                    ],
                    [
                        'q'       => 'defineEmits() trong Vue 3 script setup dùng để làm gì?',
                        'a'       => 'Nhận props từ component cha',
                        'b'       => 'Khai báo các custom events mà component có thể phát ra',
                        'c'       => 'Lắng nghe native DOM events',
                        'd'       => 'Định nghĩa computed properties',
                        'correct' => 'B',
                    ],
                ],
            ],
            [
                'course_name' => 'Python & Machine Learning Cơ Bản',
                'title'       => 'Kiểm tra kiến thức Python cơ bản',
                'description' => 'Bài kiểm tra sau khi hoàn thành Chương 1. Thời gian: 10 phút, 5 câu hỏi.',
                'questions'   => [
                    [
                        'q'       => 'Trong Python, kiểu dữ liệu nào là bất biến (immutable)?',
                        'a'       => 'list',
                        'b'       => 'dict',
                        'c'       => 'tuple',
                        'd'       => 'set',
                        'correct' => 'C',
                    ],
                    [
                        'q'       => 'NumPy được sử dụng chủ yếu để làm gì?',
                        'a'       => 'Xây dựng web API với Python',
                        'b'       => 'Tính toán số học hiệu năng cao với mảng đa chiều',
                        'c'       => 'Tạo giao diện đồ họa',
                        'd'       => 'Kết nối và truy vấn database',
                        'correct' => 'B',
                    ],
                    [
                        'q'       => 'Pandas DataFrame là gì?',
                        'a'       => 'Một loại vòng lặp đặc biệt trong Python',
                        'b'       => 'Cấu trúc dữ liệu 2 chiều dạng bảng với nhãn hàng và cột',
                        'c'       => 'Thư viện machine learning chính của Python',
                        'd'       => 'Web framework tương tự Django',
                        'correct' => 'B',
                    ],
                    [
                        'q'       => 'Supervised learning khác unsupervised learning ở điểm nào?',
                        'a'       => 'Supervised dùng nhiều dữ liệu hơn',
                        'b'       => 'Supervised học từ dữ liệu đã có nhãn, unsupervised tự tìm cấu trúc',
                        'c'       => 'Không có sự khác biệt về kết quả',
                        'd'       => 'Unsupervised luôn cho kết quả chính xác hơn',
                        'correct' => 'B',
                    ],
                    [
                        'q'       => 'Hàm train_test_split() trong Scikit-learn dùng để làm gì?',
                        'a'       => 'Huấn luyện model tự động',
                        'b'       => 'Chia dataset thành tập train và tập test',
                        'c'       => 'Chuẩn hóa (normalize) dữ liệu đầu vào',
                        'd'       => 'Đánh giá độ chính xác của model',
                        'correct' => 'B',
                    ],
                ],
            ],
        ];
    }
}
```

- [ ] **Step 2: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add Modules/Quiz/database/seeders/QuizDatabaseSeeder.php && git commit -m 'feat(quiz): create QuizDatabaseSeeder with 3 quizzes and 15 questions'" | cat
```

---

## Task 7: Create LessonProgressSeeder

**Files:**
- Create: `Modules/Lessons/database/seeders/LessonProgressSeeder.php`

For each enrollment, seeds progress for 50–70% of the course's lessons. Uses `mt_srand` for reproducible randomness.

- [ ] **Step 1: Create the file**

```php
<?php

namespace Modules\Lessons\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Lessons\Models\Lesson;

class LessonProgressSeeder extends Seeder
{
    public function run(): void
    {
        $enrollments = DB::table('students_course')->get();

        if ($enrollments->isEmpty()) {
            $this->command->warn('LessonProgressSeeder: No enrollments found. Run OrderSeeder first.');
            return;
        }

        $inserted = 0;

        foreach ($enrollments as $enrollment) {
            $lessons = Lesson::where('course_id', $enrollment->course_id)
                ->where('status', 1)
                ->orderBy('order')
                ->get(['id', 'duration']);

            if ($lessons->isEmpty()) {
                continue;
            }

            // Reproducible randomness per student+course combination
            mt_srand($enrollment->student_id * 1000 + $enrollment->course_id);

            $ratio    = mt_rand(50, 70) / 100;
            $count    = max(1, (int) round($lessons->count() * $ratio));
            $selected = $lessons->take($count); // take first N (watch in order)

            $enrolledAt = Carbon::parse($enrollment->enrolled_at);

            foreach ($selected as $idx => $lesson) {
                $isCompleted   = $idx < (int) ($count * 0.6); // first 60% are completed
                $duration      = $lesson->duration ?? 600;

                $watchedSeconds = $isCompleted
                    ? $duration
                    : (int) ($duration * (mt_rand(30, 80) / 100));

                $completedAt = $isCompleted
                    ? $enrolledAt->copy()->addDays($idx + 1)->addHours(rand(1, 6))
                    : null;

                DB::table('lesson_progress')->insertOrIgnore([
                    'student_id'     => $enrollment->student_id,
                    'lesson_id'      => $lesson->id,
                    'course_id'      => $enrollment->course_id,
                    'is_completed'   => $isCompleted ? 1 : 0,
                    'watched_seconds' => $watchedSeconds,
                    'completed_at'   => $completedAt,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

                $inserted++;
            }
        }

        $this->command->info("LessonProgressSeeder: Seeded {$inserted} lesson progress records.");
    }
}
```

- [ ] **Step 2: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add Modules/Lessons/database/seeders/LessonProgressSeeder.php && git commit -m 'feat(lessons): create LessonProgressSeeder for enrolled student progress'" | cat
```

---

## Task 8: Rewrite PostsDatabaseSeeder — real titles and content

**Files:**
- Modify: `Modules/Posts/database/seeders/PostsDatabaseSeeder.php`

Replaces random string titles and lorem ipsum with real Vietnamese tech blog content. Also fixes the `App\Models\User` import (wrong namespace — should be `Modules\Users\Models\User`).

- [ ] **Step 1: Replace the entire file content**

```php
<?php

namespace Modules\Posts\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Posts\Models\Post;
use Modules\Posts\Models\PostCategory;
use Modules\Posts\Models\PostComment;
use Modules\Posts\Models\Tag;
use Modules\Users\Models\User;

class PostsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::first()?->id ?? 1;

        // Post categories
        $categoryData = [
            ['name' => 'Công nghệ',  'slug' => 'cong-nghe',  'description' => 'Tin tức công nghệ mới nhất'],
            ['name' => 'Lập trình',  'slug' => 'lap-trinh',  'description' => 'Hướng dẫn và kiến thức lập trình'],
            ['name' => 'Kỹ năng mềm', 'slug' => 'ky-nang-mem', 'description' => 'Phát triển bản thân'],
            ['name' => 'Thông báo',  'slug' => 'thong-bao',  'description' => 'Thông báo từ hệ thống'],
        ];
        foreach ($categoryData as $cat) {
            PostCategory::updateOrCreate(['slug' => $cat['slug']], $cat);
        }

        // Tags
        $tagNames = ['Laravel', 'VueJS', 'React', 'PHP', 'JavaScript', 'Python', 'DevOps', 'Career', 'IELTS', 'Tips'];
        foreach ($tagNames as $tagName) {
            Tag::updateOrCreate(['name' => $tagName], ['slug' => Str::slug($tagName)]);
        }

        $allCategories = PostCategory::all()->keyBy('slug');
        $allTags       = Tag::all()->keyBy('name');

        $posts = $this->postData();

        foreach ($posts as $data) {
            $post = Post::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'title'            => $data['title'],
                    'slug'             => $data['slug'],
                    'content'          => $data['content'],
                    'thumbnail'        => $data['thumbnail'],
                    'author_id'        => $adminId,
                    'post_category_id' => $allCategories[$data['category']]?->id,
                    'is_published'     => true,
                    'published_at'     => now()->subDays($data['days_ago']),
                    'views'            => rand(50, 1500),
                ]
            );

            // Attach tags
            $tagIds = collect($data['tags'])->map(fn ($t) => $allTags[$t]?->id)->filter()->values()->toArray();
            $post->tags()->sync($tagIds);

            // Add 2–4 comments
            for ($j = 1; $j <= rand(2, 4); $j++) {
                PostComment::firstOrCreate(
                    ['post_id' => $post->id, 'content' => "Bình luận mẫu #{$j} cho bài '{$post->title}'"],
                    [
                        'user_id'     => $adminId,
                        'user_type'   => 'admin',
                        'is_approved' => (bool) rand(0, 1),
                    ]
                );
            }
        }

        $this->command->info('PostsDatabaseSeeder: Seeded ' . count($posts) . ' posts.');
    }

    private function postData(): array
    {
        $p = '<p>';
        $ep = '</p>';

        return [
            [
                'title'    => 'Laravel 12 có gì mới? Những tính năng nổi bật bạn cần biết',
                'slug'     => 'laravel-12-tinh-nang-noi-bat',
                'category' => 'lap-trinh',
                'tags'     => ['Laravel', 'PHP'],
                'days_ago' => 5,
                'thumbnail' => 'https://picsum.photos/seed/laravel12/800/450',
                'content'  => $p . 'Laravel 12 vừa ra mắt với hàng loạt cải tiến đáng chú ý, từ hiệu năng routing được tối ưu đến cú pháp Eloquent gọn gàng hơn. Đây là bản phát hành lớn nhất trong lịch sử framework PHP phổ biến này.' . $ep .
                              $p . 'Trong bài viết này, chúng ta sẽ cùng khám phá các tính năng mới: Lazy Collections được nâng cấp, Model casts kiểu mới, và cải tiến đáng kể trong hệ thống Queue. Nếu bạn đang dùng Laravel 11, việc nâng cấp rất đơn giản và không phá vỡ backward compatibility.' . $ep .
                              $p . 'Đặc biệt, Laravel 12 tích hợp chặt chẽ hơn với Reverb (WebSocket server) và Folio (file-based routing), giúp developer xây dựng ứng dụng real-time nhanh hơn bao giờ hết.' . $ep,
            ],
            [
                'title'    => 'Lộ trình học Vue 3 từ zero đến có việc làm trong 6 tháng',
                'slug'     => 'lo-trinh-hoc-vue-3-tu-zero-den-co-viec',
                'category' => 'lap-trinh',
                'tags'     => ['VueJS', 'JavaScript'],
                'days_ago' => 12,
                'thumbnail' => 'https://picsum.photos/seed/vue3/800/450',
                'content'  => $p . 'Vue.js 3 là một trong những frontend framework được ưa chuộng nhất hiện nay, đặc biệt tại thị trường Việt Nam. Composition API mang lại cách viết code linh hoạt và tái sử dụng cao hơn so với Options API của Vue 2.' . $ep .
                              $p . 'Lộ trình 6 tháng được chia thành 3 giai đoạn: Tháng 1-2 học nền tảng HTML/CSS/JavaScript ES6+. Tháng 3-4 học Vue 3 core, Vue Router, và Pinia. Tháng 5-6 thực hành dự án thực tế và tích hợp REST API.' . $ep .
                              $p . 'Với sự hỗ trợ của cộng đồng Vue.js Việt Nam ngày càng lớn mạnh, đây là thời điểm tuyệt vời để bắt đầu. Hàng trăm công ty tuyển dụng Vue.js developer với mức lương từ 15-30 triệu đồng/tháng.' . $ep,
            ],
            [
                'title'    => 'Docker Compose vs Kubernetes: Khi nào nên dùng cái nào?',
                'slug'     => 'docker-compose-vs-kubernetes-khi-nao-dung',
                'category' => 'cong-nghe',
                'tags'     => ['DevOps'],
                'days_ago' => 20,
                'thumbnail' => 'https://picsum.photos/seed/docker/800/450',
                'content'  => $p . 'Đây là câu hỏi mà hầu hết developer khi bước vào thế giới container đều gặp phải. Docker Compose và Kubernetes đều quản lý container, nhưng ở quy mô và mục đích rất khác nhau.' . $ep .
                              $p . 'Docker Compose phù hợp cho môi trường development local, staging nhỏ, hoặc production với 1-3 server. Cấu hình đơn giản bằng một file docker-compose.yml, dễ debug, không cần infrastructure phức tạp.' . $ep .
                              $p . 'Kubernetes (K8s) dành cho production scale lớn: auto-scaling, self-healing, rolling deployment, và multi-region. Chi phí vận hành cao hơn nhưng đổi lại là độ tin cậy và khả năng mở rộng vượt trội. Quy tắc đơn giản: dưới 5 server thì dùng Compose, trên 5 server thì cân nhắc K8s.' . $ep,
            ],
            [
                'title'    => '5 kỹ năng thiết yếu cho lập trình viên backend năm 2026',
                'slug'     => '5-ky-nang-thiet-yeu-cho-backend-developer-2026',
                'category' => 'ky-nang-mem',
                'tags'     => ['Career', 'Tips'],
                'days_ago' => 30,
                'thumbnail' => 'https://picsum.photos/seed/backend2026/800/450',
                'content'  => $p . 'Năm 2026, thị trường backend developer tiếp tục nóng nhưng cũng đòi hỏi cao hơn. Ngoài việc biết một ngôn ngữ lập trình, nhà tuyển dụng kỳ vọng gì ở một backend developer cấp trung?' . $ep .
                              $p . 'Năm kỹ năng không thể thiếu: (1) API Design chuẩn RESTful/GraphQL; (2) Database optimization — indexing, query planning; (3) Bảo mật cơ bản — OWASP Top 10, JWT, OAuth2; (4) CI/CD và containerization với Docker; (5) Hiểu về cloud services — AWS/GCP cơ bản.' . $ep .
                              $p . 'Quan trọng không kém là kỹ năng mềm: đọc hiểu requirement, viết tài liệu kỹ thuật rõ ràng, và làm việc hiệu quả trong môi trường Agile. Developer giỏi kỹ thuật nhưng thiếu kỹ năng mềm khó thăng tiến.' . $ep,
            ],
            [
                'title'    => 'IELTS 7.0 trong 6 tháng: Lộ trình và tài liệu hiệu quả nhất',
                'slug'     => 'ielts-7-trong-6-thang-lo-trinh-tai-lieu',
                'category' => 'ky-nang-mem',
                'tags'     => ['IELTS', 'Tips'],
                'days_ago' => 45,
                'thumbnail' => 'https://picsum.photos/seed/ielts/800/450',
                'content'  => $p . 'Đạt IELTS 7.0 trong 6 tháng là mục tiêu hoàn toàn khả thi nếu bạn đang ở band 5.5-6.0 và có kế hoạch học đúng hướng. Bí quyết không phải là học nhiều mà là học đúng.' . $ep .
                              $p . 'Tháng 1-2: Xây nền tảng — Cambridge IELTS books (vol 17-18), luyện phát âm với Elsa Speak, học 10 từ vựng học thuật/ngày. Tháng 3-4: Luyện kỹ năng — Reading strategies (skimming/scanning), Listening note-taking, Writing Task 1 Academic. Tháng 5-6: Mock tests và fix điểm yếu.' . $ep .
                              $p . 'Tài liệu không thể thiếu: Cambridge IELTS 15-18, Vocabulary for IELTS (Collins), và IELTS Liz website miễn phí. Quan trọng nhất: luyện đề thật hàng tuần và track điểm tiến bộ.' . $ep,
            ],
            [
                'title'    => 'Clean Code trong PHP: 10 nguyên tắc giúp code dễ bảo trì',
                'slug'     => 'clean-code-php-10-nguyen-tac',
                'category' => 'lap-trinh',
                'tags'     => ['PHP', 'Laravel', 'Tips'],
                'days_ago' => 60,
                'thumbnail' => 'https://picsum.photos/seed/cleancode/800/450',
                'content'  => $p . 'Code hoạt động được chỉ là điều kiện cần, không phải điều kiện đủ. Code tốt phải dễ đọc, dễ test, và dễ bảo trì. Đây là 10 nguyên tắc Clean Code áp dụng trong PHP và Laravel mà mọi developer nên biết.' . $ep .
                              $p . 'Top 5 nguyên tắc quan trọng nhất: (1) Đặt tên biến/hàm mô tả đủ nghĩa — không dùng $a, $temp; (2) Hàm chỉ làm một việc (Single Responsibility); (3) Tránh magic numbers — dùng constants; (4) Comment giải thích WHY không phải WHAT; (5) Không lặp code — DRY (Don't Repeat Yourself).' . $ep .
                              $p . 'Trong Laravel cụ thể: dùng Form Requests cho validation, Resources cho API response, Services cho business logic phức tạp. Controller chỉ gọi delegate, không chứa business logic.' . $ep,
            ],
            [
                'title'    => 'Ra mắt khóa học Python & Machine Learning — Đặc biệt giảm 30%',
                'slug'     => 'ra-mat-khoa-hoc-python-machine-learning',
                'category' => 'thong-bao',
                'tags'     => ['Python', 'Tips'],
                'days_ago' => 8,
                'thumbnail' => 'https://picsum.photos/seed/pythonml/800/450',
                'content'  => $p . 'Chúng tôi vui mừng thông báo ra mắt khóa học "Python & Machine Learning Cơ Bản" — được thiết kế dành cho người hoàn toàn chưa có kinh nghiệm lập trình muốn bước vào lĩnh vực Data Science.' . $ep .
                              $p . 'Khóa học bao gồm hơn 80 bài học video, 15 bài tập thực hành có chấm điểm tự động, và 5 dự án thực tế: phân tích dữ liệu COVID, dự đoán giá nhà, phân loại email spam, nhận diện chữ số viết tay và chatbot đơn giản.' . $ep .
                              $p . 'Nhân dịp ra mắt, toàn bộ học viên đăng ký trong tuần đầu sẽ nhận ưu đãi 30% và quyền truy cập vĩnh viễn cùng certificate hoàn thành. Đừng bỏ lỡ!' . $ep,
            ],
            [
                'title'    => 'Tại sao Flutter là lựa chọn hàng đầu cho mobile development 2026?',
                'slug'     => 'flutter-lua-chon-hang-dau-mobile-2026',
                'category' => 'cong-nghe',
                'tags'     => ['Career'],
                'days_ago' => 75,
                'thumbnail' => 'https://picsum.photos/seed/flutter2026/800/450',
                'content'  => $p . 'Flutter của Google tiếp tục khẳng định vị thế dẫn đầu trong phát triển ứng dụng di động đa nền tảng. Với hơn 150.000 ứng dụng trên App Store và Google Play, Flutter đã vượt qua React Native về market share tại nhiều thị trường.' . $ep .
                              $p . 'Điểm mạnh của Flutter: (1) Một codebase cho iOS, Android, Web, Desktop; (2) Hiệu năng gần native nhờ biên dịch sang native ARM; (3) Hot reload giúp phát triển nhanh; (4) Hệ sinh thái pub.dev phong phú với 30.000+ package.' . $ep .
                              $p . 'Tại Việt Nam, nhu cầu tuyển Flutter developer tăng 200% trong 2 năm qua. Mức lương trung bình 20-35 triệu đồng/tháng. Đây là thời điểm vàng để học Flutter.' . $ep,
            ],
            [
                'title'    => 'Cách học tiếng Nhật hiệu quả với phương pháp shadowing',
                'slug'     => 'hoc-tieng-nhat-phuong-phap-shadowing',
                'category' => 'ky-nang-mem',
                'tags'     => ['Tips'],
                'days_ago' => 90,
                'thumbnail' => 'https://picsum.photos/seed/japanese/800/450',
                'content'  => $p . 'Shadowing là phương pháp học ngoại ngữ bằng cách lặp lại đồng thời (hoặc gần đồng thời) âm thanh nghe được. Được phát triển bởi giáo sư Alexander Arguelles, phương pháp này đặc biệt hiệu quả cho tiếng Nhật — ngôn ngữ có ngữ điệu và nhịp điệu phức tạp.' . $ep .
                              $p . 'Cách thực hành: Bước 1 — Nghe bản gốc 3 lần, không nhìn script. Bước 2 — Nhìn script và đọc theo. Bước 3 — Che script và shadowing. Mỗi ngày 20-30 phút, kiên trì trong 3 tháng sẽ thấy kết quả rõ rệt.' . $ep .
                              $p . 'Tài liệu tốt nhất để shadowing tiếng Nhật: NHK Web Easy, Japanese Pod 101, và anime có phụ đề tiếng Nhật. Quan trọng là chọn level phù hợp — không quá dễ cũng không quá khó.' . $ep,
            ],
            [
                'title'    => 'Tổng kết tháng 5/2026: Top học viên xuất sắc và thành tích nổi bật',
                'slug'     => 'tong-ket-thang-5-2026-hoc-vien-xuat-sac',
                'category' => 'thong-bao',
                'tags'     => ['Tips'],
                'days_ago' => 2,
                'thumbnail' => 'https://picsum.photos/seed/summary0526/800/450',
                'content'  => $p . 'Tháng 5/2026 là tháng có lượng học viên hoàn thành khóa học cao nhất kể từ khi nền tảng ra mắt. Tổng cộng hơn 420 certificate đã được cấp, trong đó khóa Laravel và Vue.js dẫn đầu với 95 và 82 certificate.' . $ep .
                              $p . 'Top 3 học viên xuất sắc tháng: (1) Nguyễn Thị Mai — hoàn thành 4 khóa trong một tháng, đạt điểm 100% quiz Laravel; (2) Trần Văn Hùng — streak học liên tục 30 ngày; (3) Lê Thị Lan — chia sẻ nhiều nhất trong cộng đồng với 48 câu trả lời hữu ích.' . $ep .
                              $p . 'Tháng 6, chúng tôi sẽ ra mắt tính năng Learning Path — lộ trình học được cá nhân hóa theo mục tiêu nghề nghiệp. Cảm ơn toàn bộ học viên đã tin tưởng và đồng hành cùng chúng tôi!' . $ep,
            ],
        ];
    }
}
```

- [ ] **Step 2: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add Modules/Posts/database/seeders/PostsDatabaseSeeder.php && git commit -m 'fix(posts): rewrite PostsDatabaseSeeder with real Vietnamese tech blog content'" | cat
```

---

## Task 9: Update DatabaseSeeder — new execution order

**Files:**
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Replace the entire file content**

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Categories\Database\Seeders\CategoriesDatabaseSeeder;
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
        ]);
    }
}
```

- [ ] **Step 2: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add database/seeders/DatabaseSeeder.php && git commit -m 'refactor(seed): update DatabaseSeeder orchestration order, remove StudentEnrollmentSeeder'" | cat
```

---

## Task 10: Full verify — migrate:fresh --seed

- [ ] **Step 1: Run fresh migration + seed**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan migrate:fresh --seed 2>&1" | cat
```

Expected output includes (no errors, and lines like):
```
Seeded: Modules\Categories\Database\Seeders\CategoriesDatabaseSeeder
Seeded: Modules\Course\Database\Seeders\CourseDatabaseSeeder
...
CategoriesDatabaseSeeder: Seeded 22 categories
OrderSeeder: 150 orders (...), ... enrollments
QuizDatabaseSeeder: Created quiz '...' with 5 questions (×3)
LessonProgressSeeder: Seeded ... lesson progress records
PostsDatabaseSeeder: Seeded 10 posts
```

- [ ] **Step 2: Verify counts via tinker**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan tinker --execute=\"
  echo 'categories: ' . \Modules\Categories\Models\Category::count() . PHP_EOL;
  echo 'courses: '    . \Modules\Course\Models\Course::count()       . PHP_EOL;
  echo 'students: '   . \Modules\Students\Models\Student::count()    . PHP_EOL;
  echo 'orders: '     . \Modules\Payment\Models\Order::count()       . PHP_EOL;
  echo 'paid orders: '. \Modules\Payment\Models\Order::where('status','paid')->count() . PHP_EOL;
  echo 'transactions: '. \Modules\Payment\Models\Transaction::count() . PHP_EOL;
  echo 'enrollments: '. \Illuminate\Support\Facades\DB::table('students_course')->count() . PHP_EOL;
  echo 'coupons: '    . \Illuminate\Support\Facades\DB::table('coupons')->count()       . PHP_EOL;
  echo 'quizzes: '    . \Modules\Quiz\Models\Quiz::count()           . PHP_EOL;
  echo 'questions: '  . \Modules\Quiz\Models\QuizQuestion::count()   . PHP_EOL;
  echo 'progress: '   . \Illuminate\Support\Facades\DB::table('lesson_progress')->count() . PHP_EOL;
  echo 'posts: '      . \Modules\Posts\Models\Post::count()          . PHP_EOL;
\" 2>&1" | cat
```

Expected ranges:
```
categories:  22
courses:     25
students:    30
orders:      ~150
paid orders: ~100-110
transactions:~100-110
enrollments: >60
coupons:     6
quizzes:     3
questions:   15
progress:    >200
posts:       10
```

- [ ] **Step 3: Spot-check teacher-course mapping**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan tinker --execute=\"
  \Modules\Course\Models\Course::with('teacher')->get()->each(function(\$c) {
    echo \$c->teacher->name . ' → ' . \$c->name . PHP_EOL;
  });
\" 2>&1" | cat
```

Expected: Nguyễn Văn An owns Laravel/Vue/Node/React courses; Hoàng Thị Em owns IELTS/Tiếng Anh/Tiếng Nhật/Tiếng Hàn, etc.

- [ ] **Step 4: Spot-check dashboard revenue by month**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan tinker --execute=\"
  \Modules\Payment\Models\Order::where('status','paid')
    ->selectRaw('YEAR(paid_at) as y, MONTH(paid_at) as m, COUNT(*) as cnt, SUM(total_amount) as rev')
    ->groupByRaw('YEAR(paid_at), MONTH(paid_at)')
    ->orderBy('y')->orderBy('m')
    ->get()->each(fn(\$r) => print(\$r->y . '-' . str_pad(\$r->m, 2, '0', STR_PAD_LEFT) . ': ' . \$r->cnt . ' orders, ' . number_format(\$r->rev) . ' VNĐ' . PHP_EOL));
\" 2>&1" | cat
```

Expected: 12 rows with increasing trend from 2025-06 to 2026-05.

- [ ] **Step 5: Commit final verification**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add -A && git status" | cat
```

No untracked or modified seed files should remain. If all clean:

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git commit -m 'feat(seed): complete seed data redesign — 150 orders, quizzes, coupons, progress' --allow-empty" | cat
```

(Use `--allow-empty` only if there's nothing new to stage; otherwise stage and commit normally.)

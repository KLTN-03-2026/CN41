# Kiến trúc Database

## 1. Thông tin kết nối

| Thuộc tính | Giá trị |
|-----------|--------|
| Engine | MySQL 8.x |
| Host | 127.0.0.1:3306 |
| Database | `e_learning` |
| Charset | utf8mb4 |
| Collation | utf8mb4_unicode_ci |

---

## 2. Sơ đồ quan hệ (ERD)

```
┌──────────┐     ┌───────────────────┐     ┌────────────────┐
│  users   │     │     teachers      │     │   categories   │
│──────────│     │───────────────────│     │────────────────│
│ id       │     │ id                │     │ id             │
│ name     │     │ name              │     │ name           │
│ email    │     │ email             │     │ slug           │
│ password │     │ bio               │     │ parent_id ─────┼─┐
│ status   │     │ avatar            │     │ _lft           │ │
│ timestamps│    │ slug              │     │ _rgt           │ │
└──────────┘     │ timestamps        │     │ depth          │ │
                 └────────┬──────────┘     │ timestamps     │ │
                          │                └───────┬────────┘ │
                          │ 1:N                    │ (self)   │
                          ▼                        │ NestedSet│
┌─────────────────────────────────┐               │          │
│             courses             │               │          │
│─────────────────────────────────│               │          │
│ id                              │◄──────────────┘          │
│ teacher_id ─────────────────────┤ N:M (categories_courses) │
│ name                            │                           │
│ slug                            │                           │
│ description                     │                           │
│ thumbnail                       │                           │
│ price (decimal 10,2)            │                           │
│ sale_price (decimal 10,2)       │                           │
│ level (enum)                    │                           │
│ status (tinyint: 0/1)           │                           │
│ total_lessons                   │                           │
│ total_students                  │                           │
│ rating (float)                  │                           │
│ deleted_at (soft delete)        │                           │
│ timestamps                      │                           │
└──────────┬──────────────────────┘                           │
           │                                                  │
     ┌─────┴──────┐                                          │
     │            │                                          │
     │ 1:N        │ N:M (students_course)                    │
     ▼            ▼                                          │
┌──────────┐  ┌──────────────────────────┐  ┌────────────────┴──────┐
│ sections │  │        students          │  │  categories_courses   │
│──────────│  │──────────────────────────│  │───────────────────────│
│ id       │  │ id                       │  │ course_id (FK)        │
│ course_id│  │ name                     │  │ category_id (FK)      │
│ title    │  │ email                    │  └───────────────────────┘
│ order    │  │ password                 │
│ timestamps│ │ avatar                   │  ┌───────────────────────┐
└────┬─────┘  │ date_of_birth            │  │   students_course     │
     │        │ bio                      │  │───────────────────────│
     │ 1:N    │ email_verified_at        │  │ course_id (FK)        │
     ▼        │ deleted_at (soft delete) │  │ student_id (FK)       │
┌──────────┐  │ timestamps               │  │ enrolled_at           │
│ lessons  │  └──────────────────────────┘  │ timestamps            │
│──────────│                                └───────────────────────┘
│ id       │
│ section_id│
│ course_id│
│ title    │
│ slug     │
│ type     │◄── enum: video/document/text/quiz
│ content  │
│ video_url│
│ video_id │
│ duration │
│ order    │
│ is_preview│
│ deleted_at│
│ timestamps│
└─────┬────┘
      │
      │ 1:1
      ▼
┌───────────────────────────┐    ┌──────────────────────┐
│          quizzes          │    │   lesson_progress    │
│───────────────────────────│    │──────────────────────│
│ id                        │    │ id                   │
│ lesson_id (FK, unique)    │    │ student_id (FK)      │
│ title                     │    │ lesson_id (FK)       │
│ description               │    │ completed_at         │
│ time_limit (minutes)      │    │ timestamps           │
│ pass_score (0-100)        │    └──────────────────────┘
│ timestamps                │
└───────┬───────────────────┘
        │
        │ 1:N
        ▼
┌─────────────────────────┐    ┌────────────────────────────┐
│     quiz_questions      │    │       quiz_attempts        │
│─────────────────────────│    │────────────────────────────│
│ id                      │    │ id                         │
│ quiz_id (FK)            │    │ quiz_id (FK)               │
│ question                │    │ student_id (FK)            │
│ option_a                │    │ score                      │
│ option_b                │    │ passed                     │
│ option_c                │    │ answers (JSON)             │
│ option_d                │    │ completed_at               │
│ correct_option (A/B/C/D)│    │ timestamps                 │
│ order                   │    └────────────────────────────┘
│ timestamps              │
└─────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│                   PAYMENT DOMAIN                         │
│                                                          │
│  ┌───────────┐    ┌────────────────┐    ┌─────────────┐ │
│  │  orders   │    │  order_items   │    │transactions │ │
│  │───────────│    │────────────────│    │─────────────│ │
│  │ id        │    │ id             │    │ id          │ │
│  │student_id │1:N │ order_id (FK)  │    │ order_id    │ │
│  │order_code │──► │ course_id (FK) │    │ vnpay_txn_no│ │
│  │total_price│    │ price          │    │ amount      │ │
│  │discount   │    │ timestamps     │    │ status      │ │
│  │final_price│    └────────────────┘    │ timestamps  │ │
│  │status     │                          └─────────────┘ │
│  │coupon_id  │                                          │
│  │timestamps │                                          │
│  └───────────┘                                          │
└──────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                      POSTS DOMAIN                           │
│                                                             │
│  ┌────────────────┐  ┌──────────┐  ┌──────────────────────┐│
│  │ post_categories│  │   tags   │  │   post_comments      ││
│  │────────────────│  │──────────│  │──────────────────────││
│  │ id             │  │ id       │  │ id                   ││
│  │ name           │  │ name     │  │ post_id (FK)         ││
│  │ slug           │  │ slug     │  │ student_id (FK)      ││
│  │ timestamps     │  │timestamps│  │ content              ││
│  └───────┬────────┘  └────┬─────┘  │ timestamps           ││
│          │                │        └──────────────────────┘│
│          │ 1:N      N:M   │                                 │
│          ▼  ┌─────────────┘                                 │
│  ┌───────────────────────────────┐                          │
│  │             posts             │                          │
│  │───────────────────────────────│                          │
│  │ id                            │                          │
│  │ post_category_id (FK)         │                          │
│  │ author_id (FK → users)        │                          │
│  │ title                         │                          │
│  │ slug                          │                          │
│  │ content (longtext)            │                          │
│  │ thumbnail                     │                          │
│  │ status (0/1)                  │                          │
│  │ timestamps                    │                          │
│  └───────────────────────────────┘                          │
│        │ N:M (post_tag pivot)                               │
└────────┼────────────────────────────────────────────────────┘
```

---

## 3. Danh sách bảng và mô tả

### 3.1 Domain người dùng

| Bảng | Rows (ước tính) | Mô tả |
|------|----------------|-------|
| `users` | Nhỏ (<100) | Staff admin, được quản lý bởi Spatie RBAC |
| `students` | Lớn | Học viên, soft delete, email verification |
| `teachers` | Nhỏ | Giảng viên, có slug để tạo trang profile |
| `student_email_verifications` | Tạm thời | Token xác minh email (one-time, 24h TTL) |

### 3.2 Domain khóa học

| Bảng | Mô tả |
|------|-------|
| `courses` | Khóa học, soft delete, cascade xuống sections/lessons |
| `sections` | Nhóm bài học trong khóa, có thứ tự (`order`) |
| `lessons` | Bài học, 4 loại: video/document/text/quiz |
| `categories` | Danh mục cây (Kalnoy NestedSet: `_lft`, `_rgt`, `depth`) |
| `categories_courses` | Pivot N:M giữa khóa học và danh mục |
| `students_course` | Pivot N:M học viên đã enroll, có `enrolled_at` |
| `lesson_progress` | Theo dõi bài học đã hoàn thành của học viên |
| `media_files` | File video/document đã upload |

### 3.3 Domain quiz

| Bảng | Mô tả |
|------|-------|
| `quizzes` | Quiz gắn với một lesson (1:1), có `time_limit`, `pass_score` |
| `quiz_questions` | Câu hỏi 4 lựa chọn A/B/C/D |
| `quiz_attempts` | Lịch sử làm bài của học viên, lưu `answers` dạng JSON |
| `quiz_generation_jobs` | Trạng thái job sinh quiz bằng AI (pending/processing/completed/failed) |

### 3.4 Domain thanh toán

| Bảng | Mô tả |
|------|-------|
| `orders` | Đơn hàng, có `order_code` unique, trạng thái (pending/paid/failed/refunded) |
| `order_items` | Chi tiết các khóa học trong đơn |
| `transactions` | Giao dịch VNPay (IPN data, HMAC verified) |
| `coupons` | Mã giảm giá, có `usage_limit` + `used_count` (race condition protection) |

### 3.5 Domain blog

| Bảng | Mô tả |
|------|-------|
| `post_categories` | Danh mục bài viết |
| `posts` | Bài viết/blog, có `author_id` → users |
| `tags` | Nhãn bài viết |
| `post_tag` | Pivot N:M giữa posts và tags |
| `post_comments` | Bình luận của học viên |

---

## 4. Conventions quan trọng

### 4.1 Naming

```
Tables:         plural snake_case          → courses, students, teachers
Pivot tables:   alphabetical snake_case    → categories_courses, students_course
Foreign keys:   singular + _id            → teacher_id, course_id, section_id
Models:         singular PascalCase        → Course, Student, Teachers
```

### 4.2 Cột chuẩn có trong hầu hết bảng

| Cột | Kiểu | Ghi chú |
|-----|------|---------|
| `id` | bigint unsigned auto_increment | PK |
| `status` | tinyint default 0 | 0 = inactive/draft, 1 = active/published |
| `slug` | varchar(255) unique | URL-friendly, regex `^[a-z0-9]+(?:-[a-z0-9]+)*$` |
| `created_at` | timestamp | Laravel auto |
| `updated_at` | timestamp | Laravel auto |
| `deleted_at` | timestamp nullable | Soft delete (Course, Lesson, Student) |

### 4.3 Kiểu dữ liệu đặc biệt

```sql
-- Giá tiền
price       DECIMAL(10, 2) DEFAULT 0
sale_price  DECIMAL(10, 2) DEFAULT 0

-- Enum cố định
level       ENUM('beginner', 'intermediate', 'advanced')
type        ENUM('video', 'document', 'text', 'quiz')   -- lessons
status      TINYINT DEFAULT 0  -- 0/1 (không dùng enum để dễ mở rộng)

-- NestedSet (categories)
_lft        INT UNSIGNED
_rgt        INT UNSIGNED
depth       INT UNSIGNED
parent_id   BIGINT UNSIGNED NULLABLE

-- Quiz
answers     JSON   -- trong quiz_attempts: {"1": "A", "2": "C", ...}
pass_score  INT    -- phần trăm (0-100)
```

---

## 5. Soft Delete và Cascade

Ba model dùng `SoftDeletes`: `Course`, `Lesson`, `Student`.

**Cascade soft delete cho Course** (được xử lý trong `Course::booted()`):

```
Course::delete()
  ├── Section::delete() cho mỗi section của course
  └── Lesson::delete() cho mỗi lesson của course

Course::restore()
  ├── Section::restore() cho mỗi section
  └── Lesson::restore() cho mỗi lesson

Course::forceDelete()
  ├── Lesson::forceDelete() (withTrashed, FK section_id trước)
  └── Section::forceDelete() (withTrashed)
```

---

## 6. Danh mục dạng cây (NestedSet)

Package `kalnoy/nestedset` cho phép truy vấn cây hiệu quả với các method:

```php
Category::withDepth()->get()          // lấy tất cả kèm depth
$category->children()                 // con trực tiếp
$category->ancestors()                // tất cả cha
$category->descendants()              // tất cả con cháu
Category::whereIsRoot()->get()        // chỉ root nodes
```

Cấu trúc cây lưu trong 3 cột: `_lft`, `_rgt`, `depth` — không cần join đệ quy.

---

## 7. Indexes quan trọng

| Bảng | Index | Mục đích |
|------|-------|---------|
| `courses` | `slug` (unique) | Tìm khóa học theo URL |
| `courses` | `teacher_id` | Filter theo giảng viên |
| `courses` | `status` | Lọc published courses |
| `lessons` | `slug` (unique) | URL bài học |
| `lessons` | `section_id` | Lấy lessons theo section |
| `students` | `email` (unique) | Đăng nhập, tránh trùng email |
| `orders` | `order_code` (unique) | Tra cứu đơn hàng |
| `students_course` | `(course_id, student_id)` (unique) | Tránh enroll trùng |
| `categories` | `_lft`, `_rgt` | NestedSet traversal |

---

## 8. Migration timeline

```
2026-03-17  users, students, student_email_verifications
2026-03-18  teachers, categories, add_date_of_birth_to_students
2026-04-07  courses, categories_courses, students_course
2026-04-07  lessons, lesson_progress
2026-04-08  sections, add_section_id_to_lessons
2026-04-10  orders, order_items, transactions
2026-04-26  post_categories, tags, posts, post_comments, post_tag, coupons
2026-05-06  quizzes, quiz_questions, quiz_attempts
2026-05-06  add_quiz_to_lesson_type_enum
2026-05-07  quiz_generation_jobs
```

Tổng cộng: **27 migrations**, không có rollback nguy hiểm — tất cả đều thêm bảng/cột mới hoặc thay đổi enum an toàn.

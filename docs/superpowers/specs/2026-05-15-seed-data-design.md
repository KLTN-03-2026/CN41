# Seed Data Redesign — Design Spec

**Date:** 2026-05-15  
**Scope:** `e-learning-backend` — all seeders  
**Goal:** Produce a single `php artisan migrate:fresh --seed` run that yields demo-ready, internally consistent data suitable for thesis presentation, feature testing, and dashboard visualisation.

---

## 1. Problems Fixed

| Problem | Location | Impact |
|---------|----------|--------|
| Category slugs `ky-nang-mem` / `thiet-ke-do-hoa` missing | `CategoriesDatabaseSeeder` | 3 courses have no category |
| `OrderSeeder` creates orders with no `transactions` records | `OrderSeeder` | Dashboard payment stats empty |
| Teachers assigned to courses randomly | `CourseDatabaseSeeder` | Each teacher sees random courses |
| `StudentsDatabaseSeeder` not called in `DatabaseSeeder` | `DatabaseSeeder` | Orphan seeder |
| `CouponsDatabaseSeeder` is empty | `CouponsDatabaseSeeder` | Coupon feature undemonstrable |
| No `QuizDatabaseSeeder` exists | — | Quiz feature undemonstrable |
| No `LessonProgressSeeder` exists | — | Progress stats empty |
| `PostsDatabaseSeeder` uses random slugs/titles | `PostsDatabaseSeeder` | Unusable for demo |
| Enrollments created independently of orders | `StudentEnrollmentSeeder` | student enrolled without paid order |
| Only 20 orders over 3-4 months | `OrderSeeder` | Dashboard chart meaningless |

---

## 2. Target Data Volumes

| Entity | Count | Notes |
|--------|-------|-------|
| Categories | 22 | 20 existing + `ky-nang-mem` + `thiet-ke-do-hoa` |
| Admin users | 2 | superadmin + admin (unchanged) |
| Teachers | 10 | Each owns a fixed domain of courses |
| Courses | 25 | Unchanged; teacher + category assignment fixed |
| Students | 30 | Realistic Vietnamese names, all verified |
| Orders | ~150 | 12-month trend, see §5 |
| Transactions | ~105 | One per paid order |
| Coupons | 6 | See §6 |
| Quizzes | 3 | Laravel, Vue.js, Python courses |
| Quiz questions | 15 | 5 per quiz |
| Lesson progress | ~300 | Derived from enrollments |
| Posts | 10 | Real titles, real categories/tags |

---

## 3. Categories

Add two root-level category nodes **after** the existing 20 are inserted:

| Name | Slug | Parent |
|------|------|--------|
| Kỹ năng mềm | `ky-nang-mem` | root |
| Thiết kế đồ họa | `thiet-ke-do-hoa` | root |

`Category::fixTree()` is called once after all inserts (existing behaviour preserved).

---

## 4. Teacher → Course Domain Mapping

Deterministic assignment — no `array_rand`. Each course specifies `teacher_slug`:

| Teacher | Domain | Courses |
|---------|--------|---------|
| Nguyễn Văn An (`nguyen-van-an`) | Web / Backend | Laravel, Vue.js, HTML/CSS/JS, React/Next, Node.js, API Design, TypeScript, Spring Boot, Git |
| Trần Thị Bình (`tran-thi-binh`) | UI/UX + Design | UI/UX Figma, Photoshop |
| Phạm Hồng Đức (`pham-hong-duc`) | Mobile | Flutter, React Native, iOS Swift |
| Vũ Đình Phú (`vu-dinh-phu`) | DevOps / Cloud | Docker, Kubernetes |
| Đặng Thị Giang (`dang-thi-giang`) | Data / AI | Python/ML, Deep Learning, MySQL, PostgreSQL |
| Hoàng Thị Em (`hoang-thi-em`) | Languages | IELTS, Tiếng Anh giao tiếp, Tiếng Nhật, Tiếng Hàn |
| Lê Minh Cường (`le-minh-cuong`) | Marketing | Kỹ Năng Thuyết Trình |

Teachers Ngô Thanh Vy (status=0) and Trịnh Văn Khoa (status=0) have no courses (inactive).

---

## 5. Orders, Transactions & Enrollments

### 5.1 Monthly distribution (12 months ending May 2026)

| Month | Orders | ~Paid (70%) |
|-------|--------|-------------|
| 2025-06 | 6 | 4 |
| 2025-07 | 8 | 6 |
| 2025-08 | 9 | 6 |
| 2025-09 | 10 | 7 |
| 2025-10 | 11 | 8 |
| 2025-11 | 13 | 9 |
| 2025-12 | 15 | 11 |
| 2026-01 | 12 | 8 |
| 2026-02 | 13 | 9 |
| 2026-03 | 15 | 11 |
| 2026-04 | 18 | 13 |
| 2026-05 | 20 | 14 |
| **Total** | **150** | **~105** |

### 5.2 Status distribution
- `paid` 70% | `pending` 15% | `failed` 10% | `cancelled` 5%

### 5.3 Transaction records
- Every `paid` order → 1 `transactions` record (`status=success`, `paid_at` = order `paid_at`)
- `gateway`: 70% `vnpay`, 30% `zalopay`
- `bank_code` samples: `NCB`, `VIETCOMBANK`, `TECHCOMBANK`, `MBBANK`, `VCB`

### 5.4 Enrollment rule
- Enrollment (`students_course`) is created **only** from paid orders, not independently
- Free courses (price = 0): enrolled directly without an order
- `enrolled_at` = order `paid_at`
- `Course::increment('total_students')` called per enrollment

### 5.5 `order_code` format
`ORD-YYYYMMDD-XXXXX` (date + 5 random chars uppercase) — human-readable.

---

## 6. Coupons

| Code | Type | Value | Min order | Max discount | Uses limit | Expires | Status |
|------|------|-------|-----------|-------------|-----------|---------|--------|
| `NEWUSER10` | percentage | 10% | 200,000 | 50,000 | unlimited | 2027-12-31 | active |
| `FLASH50` | fixed | 50,000 | 300,000 | — | 100 | 2026-06-30 | active |
| `SUMMER30` | percentage | 30% | 500,000 | 150,000 | 50 | 2026-07-31 | active |
| `TECH200` | fixed | 200,000 | 800,000 | — | 30 | 2026-05-31 | active |
| `EXPIRED2025` | percentage | 20% | 100,000 | — | unlimited | 2025-12-31 | **inactive** |
| `VIP500` | fixed | 500,000 | 2,000,000 | — | 10 | 2026-12-31 | active |

`used_count` for active coupons is set to a plausible non-zero value (e.g., FLASH50 → 37 used).

---

## 7. Quizzes

Three quizzes attached to the **first `document`-type lesson** of the first section of three courses:

| Course | Quiz title | Questions |
|--------|-----------|-----------|
| Laravel 12 Từ Cơ Bản Đến Nâng Cao | Kiểm tra kiến thức Laravel cơ bản | 5 |
| Vue.js 3 & Pinia Thực Chiến | Kiểm tra kiến thức Vue 3 | 5 |
| Python & Machine Learning Cơ Bản | Kiểm tra kiến thức Python | 5 |

Each question has options A/B/C/D and a `correct_option`. Questions are domain-specific (not lorem ipsum). `time_limit=10` minutes, `max_attempts=3`, `status=1`.

---

## 8. Lesson Progress

For each `(student, course)` enrollment, seed progress for **50–70%** of lessons in that course (random slice):
- 60% of selected lessons: `is_completed=1`, `watched_seconds=full duration`, `completed_at` set
- 40%: `is_completed=0`, `watched_seconds` = random 30–80% of duration

This produces realistic-looking per-student progress on the dashboard without over-seeding.

---

## 9. Students (30 accounts)

30 students with real Vietnamese names, all `email_verified_at=now()`, password=`password`. Includes:
- The existing demo account `student@elearning.com` (Student Demo)
- One unverified: `student-unverified@elearning.com`
- 28 new accounts with `@gmail.com` emails

---

## 10. Posts (10 articles)

Real titles in Vietnamese tech blog style:

1. "Laravel 12 có gì mới? Những tính năng nổi bật bạn cần biết"
2. "Lộ trình học Vue 3 từ zero đến có việc làm trong 6 tháng"
3. "Docker Compose vs Kubernetes: Khi nào nên dùng cái nào?"
4. "5 kỹ năng thiết yếu cho lập trình viên backend năm 2026"
5. "IELTS 7.0 trong 6 tháng: Lộ trình và tài liệu hiệu quả nhất"
6. "Clean Code trong PHP: 10 nguyên tắc giúp code dễ bảo trì"
7. "Giới thiệu khóa học Python & Machine Learning mới ra mắt"
8. "Tại sao Flutter là lựa chọn hàng đầu cho mobile development 2026?"
9. "Cách học tiếng Nhật hiệu quả với phương pháp shadowing"
10. "Tổng kết tháng 5: Học viên xuất sắc và thành tích nổi bật"

Each post has real `slug`, `content` (3–4 paragraphs Vietnamese), appropriate `post_category_id`, 2–3 tags, `is_published=true`.

---

## 11. DatabaseSeeder Execution Order

```php
1.  RolePermissionSeeder          // Permissions + roles (unchanged)
2.  AdminUserSeeder               // superadmin, admin (unchanged)
3.  CategoriesDatabaseSeeder      // MODIFIED: +ky-nang-mem, +thiet-ke-do-hoa
4.  TeachersDatabaseSeeder        // UNCHANGED structure; teacher→user linking kept
5.  MediaFileSeeder               // UNCHANGED
6.  CourseDatabaseSeeder          // MODIFIED: teacher deterministic, slugs fixed
7.  LessonDatabaseSeeder          // UNCHANGED (uses MediaFile)
8.  QuizDatabaseSeeder            // NEW
9.  StudentsDatabaseSeeder        // REWRITTEN: 30 students
10. OrderSeeder                   // REWRITTEN: 150 orders + transactions + enrollments
11. CouponsDatabaseSeeder         // REWRITTEN: 6 coupons
12. LessonProgressSeeder          // NEW
13. PostsDatabaseSeeder           // REWRITTEN: real titles/content
```

`StudentEnrollmentSeeder` is **removed** — its logic is absorbed into `OrderSeeder` (paid orders → enroll) and `StudentsDatabaseSeeder` (student creation).

---

## 12. Files Changed / Created

| File | Action |
|------|--------|
| `Modules/Categories/database/seeders/CategoriesDatabaseSeeder.php` | Modify |
| `Modules/Course/database/seeders/CourseDatabaseSeeder.php` | Modify |
| `Modules/Students/database/seeders/StudentsDatabaseSeeder.php` | Rewrite |
| `Modules/Payment/database/seeders/OrderSeeder.php` | Rewrite |
| `Modules/Coupons/database/seeders/CouponsDatabaseSeeder.php` | Rewrite |
| `Modules/Posts/database/seeders/PostsDatabaseSeeder.php` | Rewrite |
| `Modules/Quiz/database/seeders/QuizDatabaseSeeder.php` | **Create** |
| `Modules/Lessons/database/seeders/LessonProgressSeeder.php` | **Create** |
| `database/seeders/DatabaseSeeder.php` | Modify (order + remove StudentEnrollmentSeeder) |

---

## 13. Constraints & Non-Goals

- `migrate:fresh --seed` must complete without errors on a clean DB
- Seed is idempotent where practical (`firstOrCreate` / `updateOrCreate` for reference data)
- No Faker library — all data is hardcoded for deterministic, stable demo output
- No changes to migrations or application code
- `TestUsersSeeder` and `LessonSeeder` (orphans) are not called and not deleted

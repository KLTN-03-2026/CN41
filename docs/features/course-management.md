# Quản lý khóa học

## 1. Tổng quan

Module `Course` quản lý toàn bộ vòng đời của khóa học: từ tạo mới (admin), phân loại danh mục, publish, cho đến học viên enroll và học. Khóa học có cấu trúc phân cấp: **Course → Sections → Lessons**.

---

## 2. Cấu trúc dữ liệu

```
courses
  │
  ├── categories_courses (N:M pivot)
  │     └── categories
  │
  ├── students_course (N:M pivot — enrolled)
  │     └── students
  │
  └── sections (1:N, có order)
        └── lessons (1:N, có order, có type)
              └── lesson_progress (per student)
```

### Trạng thái khóa học (`status`)

| Giá trị | Ý nghĩa |
|---------|---------|
| `0` | Draft — chỉ admin thấy |
| `1` | Published — học viên có thể thấy và mua |

---

## 3. API Endpoints

### Admin

| Method | Endpoint | Permission | Mô tả |
|--------|----------|-----------|-------|
| GET | `/api/v1/admin/courses` | courses.view | Danh sách (có filter, phân trang) |
| POST | `/api/v1/admin/courses` | courses.create | Tạo khóa học |
| GET | `/api/v1/admin/courses/{id}` | courses.view | Chi tiết |
| PATCH | `/api/v1/admin/courses/{id}` | courses.edit | Cập nhật |
| DELETE | `/api/v1/admin/courses/{id}` | courses.delete | Soft delete |
| PATCH | `/api/v1/admin/courses/{id}/toggle-status` | courses.edit | Bật/tắt publish |
| GET | `/api/v1/admin/courses/trashed` | courses.view | Danh sách đã xóa |
| PATCH | `/api/v1/admin/courses/{id}/restore` | courses.edit | Khôi phục |
| DELETE | `/api/v1/admin/courses/{id}/force-delete` | courses.delete | Xóa vĩnh viễn |
| DELETE | `/api/v1/admin/courses/bulk-delete` | courses.delete | Xóa hàng loạt |
| PATCH | `/api/v1/admin/courses/bulk-status` | courses.edit | Đổi status hàng loạt |

### Public (không cần auth)

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/v1/courses` | Danh sách khóa published (filter, search, phân trang) |
| GET | `/api/v1/courses/featured` | Top khóa học nổi bật |
| GET | `/api/v1/courses/{slug}` | Chi tiết khóa học |
| GET | `/api/v1/courses/{slug}/lessons` | Curriculum (ẩn content nếu chưa mua) |
| GET | `/api/v1/courses/{slug}/lessons/{lessonSlug}/preview` | Xem bài thử (is_preview=1) |

### Student (auth:api + email.verified)

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/v1/my-courses` | Danh sách khóa đã enroll |
| POST | `/api/v1/courses/{slug}/enroll-free` | Enroll khóa miễn phí (price=0) |

---

## 4. Luồng tạo khóa học (Admin)

```
Admin mở CourseFormPage.vue
  │
  ├── Nhập: name, slug, description, level, price, sale_price
  ├── Chọn teacher (dropdown)
  ├── Chọn categories (multi-select từ cây NestedSet)
  ├── Upload thumbnail
  │
  │  POST /api/v1/admin/courses
  │  Body: { name, slug, teacher_id, category_ids[], price, sale_price, level, thumbnail_id }
  ▼
AdminCourseController::store()
  │
  ├── StoreCourseRequest validation:
  │     slug: unique:courses + regex
  │     teacher_id: exists:teachers
  │     category_ids.*: exists:categories
  │     sale_price: lte:price
  │
  ├── DB::transaction():
  │     ├── CourseRepository::create(validated)
  │     └── CourseRepository::syncCategories(course_id, category_ids)
  │
  └── Return 201: CourseResource

Admin tiếp tục → quản lý Sections + Lessons trong cùng trang (SectionsLessonsManager)
```

---

## 5. Luồng quản lý Sections & Lessons

Được xử lý bởi component `SectionsLessonsManager.vue` và composable `useSectionsManager.ts`:

```
CourseFormPage
  │
  └── SectionsLessonsManager
        │
        ├── Hiển thị danh sách sections (có drag-drop reorder)
        │
        ├── Mỗi section có:
        │     ├── Tiêu đề section (inline edit)
        │     ├── Danh sách lessons trong section
        │     └── Nút thêm lesson
        │
        └── Mỗi lesson có:
              ├── Type: video / document / text / quiz
              ├── is_preview toggle (cho phép xem thử)
              └── Nút edit → mở LessonFormModal
```

**API Sections:**
```
POST   /api/v1/admin/sections              — Tạo section
PATCH  /api/v1/admin/sections/{id}         — Cập nhật
DELETE /api/v1/admin/sections/{id}         — Xóa
POST   /api/v1/admin/sections/reorder      — Cập nhật thứ tự hàng loạt
```

**API Lessons:**
```
POST   /api/v1/admin/lessons               — Tạo lesson
PATCH  /api/v1/admin/lessons/{id}          — Cập nhật
DELETE /api/v1/admin/lessons/{id}          — Soft delete
POST   /api/v1/admin/lessons/reorder       — Cập nhật thứ tự
POST   /api/v1/admin/lessons/bulk-action   — publish/unpublish/assign-section
```

---

## 6. Luồng học viên xem & enroll

### Xem danh sách khóa học (public)

```
GET /api/v1/courses?search=laravel&level=beginner&category_id=3&page=1&per_page=12
  │
  ▼
CourseController::publicIndex()
  │
  ├── Filter: search (name LIKE), level, category_id, teacher_id
  ├── Chỉ trả khóa status = 1 (published)
  └── Return: CourseResource[] + pagination
```

### Xem chi tiết + curriculum

```
GET /api/v1/courses/{slug}
  → Thông tin khóa học + teacher + categories + tổng số bài + tổng thời lượng

GET /api/v1/courses/{slug}/lessons
  → Danh sách sections + lessons
  → [Học viên chưa mua]: content, video_url = null (ẩn)
  → [is_preview = 1]: luôn hiển thị đủ
  → [Học viên đã mua]: đầy đủ thông tin + progress
```

### Enroll khóa học

**Khóa miễn phí (price = 0):**
```
POST /api/v1/courses/{slug}/enroll-free
  ├── Kiểm tra price = 0
  ├── Kiểm tra chưa enrolled
  └── Insert students_course: { course_id, student_id, enrolled_at }
```

**Khóa có phí:** Phải qua luồng thanh toán VNPay — xem [payment-vnpay.md](payment-vnpay.md).

---

## 7. Soft Delete và Cascade

Khi xóa Course (soft delete), hệ thống tự động cascade:

```
Course::delete()
  ├── Section::delete() cho mỗi section
  └── Lesson::delete() cho mỗi lesson

Course::restore()
  ├── Section::restore()
  └── Lesson::restore()

Course::forceDelete()
  ├── Lesson::forceDelete() (withTrashed)
  └── Section::forceDelete() (withTrashed)
```

Logic này được xử lý trong `Course::booted()` dùng Eloquent model events, đảm bảo model events (deleting/deleted) được fire đúng.

---

## 8. Ví dụ response

### GET `/api/v1/courses/{slug}` — Chi tiết khóa

```json
{
  "success": true,
  "data": {
    "id": 5,
    "name": "Laravel từ cơ bản đến nâng cao",
    "slug": "laravel-co-ban-nang-cao",
    "description": "...",
    "thumbnail": "http://localhost:8000/storage/thumbnails/laravel.jpg",
    "price": "500000.00",
    "sale_price": "350000.00",
    "level": "beginner",
    "status": 1,
    "total_lessons": 24,
    "total_students": 152,
    "rating": 4.7,
    "teacher": { "id": 2, "name": "Nguyễn Văn A", "avatar": "..." },
    "categories": [{ "id": 3, "name": "Lập trình Web" }]
  }
}
```

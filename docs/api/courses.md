# API Reference — Courses

Base URL: `http://localhost:8000/api/v1`

---

## Admin

### GET `/admin/courses`

**Middleware:** `auth:admin`, `permission:courses.view`

**Query params:**
| Param | Type | Mô tả |
|-------|------|-------|
| `search` | string | Tìm theo tên |
| `status` | 0\|1 | Lọc theo trạng thái |
| `teacher_id` | int | Lọc theo giảng viên |
| `level` | string | beginner\|intermediate\|advanced |
| `page` | int | Trang hiện tại (default: 1) |
| `per_page` | int | Số bản ghi/trang (default: 15, max: 100) |

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Laravel từ cơ bản",
      "slug": "laravel-co-ban",
      "thumbnail": "http://localhost:8000/storage/thumbnails/laravel.jpg",
      "price": "500000.00",
      "sale_price": "350000.00",
      "level": "beginner",
      "status": 1,
      "total_lessons": 24,
      "total_students": 152,
      "teacher": { "id": 2, "name": "Nguyễn Văn A" }
    }
  ],
  "pagination": { "current_page": 1, "last_page": 3, "per_page": 15, "total": 40 }
}
```

---

### POST `/admin/courses`

**Middleware:** `auth:admin`, `permission:courses.create`

**Request Body:**
```json
{
  "name": "Laravel từ cơ bản đến nâng cao",
  "slug": "laravel-co-ban-nang-cao",
  "teacher_id": 2,
  "category_ids": [3, 5],
  "description": "Khóa học Laravel toàn diện...",
  "thumbnail": "thumbnails/laravel.jpg",
  "price": 500000,
  "sale_price": 350000,
  "level": "beginner",
  "status": 0
}
```

**Validation:**
| Field | Rules |
|-------|-------|
| `name` | required, string, max:255 |
| `slug` | required, unique:courses, regex:`^[a-z0-9]+(?:-[a-z0-9]+)*$` |
| `teacher_id` | required, exists:teachers,id |
| `category_ids.*` | integer, exists:categories,id |
| `price` | required, numeric, min:0 |
| `sale_price` | nullable, numeric, lte:price |
| `level` | required, in:beginner,intermediate,advanced |
| `status` | nullable, in:0,1 |

**Response 201:** `CourseResource`

---

### GET `/admin/courses/{id}`

**Response 200:** Chi tiết course + teacher + categories + sections count.

---

### PATCH `/admin/courses/{id}`

Tương tự POST nhưng tất cả fields đều `sometimes`. `slug` validate unique bỏ qua id hiện tại.

---

### DELETE `/admin/courses/{id}`

Soft delete. Cascade soft delete xuống sections + lessons.

**Response 200:** `{ "success": true, "message": "Xóa thành công" }`

---

### PATCH `/admin/courses/{id}/toggle-status`

**Response 200:** `{ "data": { "status": 1 } }`

---

### GET `/admin/courses/trashed`

Danh sách courses đã soft delete. Hỗ trợ phân trang.

---

### PATCH `/admin/courses/{id}/restore`

Khôi phục course + cascade restore sections + lessons.

---

### DELETE `/admin/courses/{id}/force-delete`

Xóa vĩnh viễn. Cascade force delete sections + lessons.

---

### DELETE `/admin/courses/bulk-delete`

```json
{ "ids": [1, 2, 3] }
```

**Validation:** `ids` required array, `ids.*` exists:courses,id và chưa bị soft delete.

---

### PATCH `/admin/courses/bulk-status`

```json
{ "ids": [1, 2], "status": 1 }
```

---

## Public

### GET `/courses`

**Query params:** `search`, `level`, `category_id`, `teacher_id`, `page`, `per_page`

Chỉ trả courses có `status = 1`.

---

### GET `/courses/featured`

Top khóa học nổi bật (rating cao, nhiều học viên).

---

### GET `/courses/{slug}`

Chi tiết khóa học + teacher + categories + tổng số bài + tổng thời lượng.

**Response 200:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Laravel từ cơ bản đến nâng cao",
    "slug": "laravel-co-ban-nang-cao",
    "description": "...",
    "thumbnail": "http://...",
    "price": "500000.00",
    "sale_price": "350000.00",
    "level": "beginner",
    "total_lessons": 24,
    "total_students": 152,
    "rating": 4.7,
    "teacher": { "id": 2, "name": "Nguyễn Văn A", "avatar": "..." },
    "categories": [{ "id": 3, "name": "Lập trình Web" }]
  }
}
```

---

### GET `/courses/{slug}/lessons`

Curriculum — danh sách sections + lessons. Content/video bị ẩn nếu chưa mua (trừ `is_preview = 1`).

---

### GET `/courses/{slug}/preview-lesson/{lesson_slug}`

Xem bài học thử. Chỉ hoạt động với lesson có `is_preview = 1`.

---

## Student (auth:api + email.verified)

### GET `/my-courses`

Danh sách khóa học đã enroll của học viên hiện tại.

---

### POST `/courses/{slug}/enroll-free`

Enroll khóa học miễn phí (`price = 0`). Lỗi 422 nếu khóa có phí hoặc đã enroll.

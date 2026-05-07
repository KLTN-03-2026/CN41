# API Reference — Lessons & Sections

Base URL: `http://localhost:8000/api/v1`

---

## Admin — Sections

### GET `/admin/courses/{course_id}/sections`

**Middleware:** `auth:admin`, `permission:lessons.view`

**Response 200:** Danh sách sections theo thứ tự `order`.

---

### POST `/admin/courses/{course_id}/sections`

**Request Body:**
```json
{ "title": "Giới thiệu", "order": 1 }
```

**Response 201:** `SectionResource`

---

### PATCH `/admin/sections/{id}`

```json
{ "title": "Chương 1 — Giới thiệu" }
```

---

### DELETE `/admin/sections/{id}`

Soft delete section. Cascade soft delete lessons trong section.

---

### POST `/admin/sections/reorder`

Cập nhật thứ tự hàng loạt:

```json
{ "items": [{ "id": 1, "order": 0 }, { "id": 2, "order": 1 }] }
```

---

### POST `/admin/sections/bulk-action`

```json
{ "ids": [1, 2], "action": "publish" }
```

Actions: `publish`, `unpublish`.

---

## Admin — Lessons

### GET `/admin/courses/{course_id}/lessons`

**Middleware:** `auth:admin`, `permission:lessons.view`

**Response 200:** Danh sách lessons kèm section info, theo thứ tự.

---

### POST `/admin/courses/{course_id}/lessons`

**Request Body:**
```json
{
  "section_id": 3,
  "title": "Cài đặt môi trường",
  "slug": "cai-dat-moi-truong",
  "type": "video",
  "video_id": 10,
  "duration": 720,
  "order": 2,
  "is_preview": false,
  "status": 1
}
```

**Validation:**
| Field | Rules |
|-------|-------|
| `section_id` | nullable, exists:sections,id |
| `title` | required, string, max:255 |
| `slug` | required, unique:lessons, regex slug |
| `type` | required, in:video,document,text,quiz |
| `video_id` | nullable, exists:media_files,id |
| `is_preview` | boolean |
| `status` | in:0,1 |

---

### PATCH `/admin/lessons/{id}`

Tương tự POST, tất cả fields `sometimes`. Slug unique bỏ qua id hiện tại.

---

### DELETE `/admin/lessons/{id}`

Soft delete.

---

### PATCH `/admin/lessons/{id}/toggle-status`

---

### POST `/admin/lessons/reorder`

```json
{ "items": [{ "id": 5, "order": 0 }, { "id": 6, "order": 1 }] }
```

---

### POST `/admin/lessons/bulk-action`

```json
{ "ids": [5, 6, 7], "action": "publish" }
```

Actions: `publish`, `unpublish`, `assign-section` (kèm `section_id`).

---

### GET `/admin/lessons/trashed`

Danh sách đã soft delete, phân trang.

---

### POST `/admin/lessons/bulk-restore`

```json
{ "ids": [5, 6] }
```

---

### DELETE `/admin/lessons/bulk-force-delete`

```json
{ "ids": [5, 6] }
```

---

## Public

### GET `/courses/{slug}/curriculum`

Curriculum công khai — chỉ trả tên section + tên lesson + type + duration. Không có URL video.

---

## Student (auth:api + email.verified)

### GET `/my-courses/{slug}/lessons`

Danh sách lessons của khóa đã enroll, kèm tiến độ từng bài.

**Response 200:**
```json
{
  "success": true,
  "data": {
    "sections": [
      {
        "id": 3,
        "title": "Giới thiệu",
        "lessons": [
          {
            "id": 5,
            "title": "Cài đặt môi trường",
            "type": "video",
            "duration": 720,
            "is_preview": false,
            "progress": { "is_completed": true, "watched_seconds": 720 }
          }
        ]
      }
    ]
  }
}
```

---

### GET `/my-courses/{slug}/lessons/{lesson_slug}`

Chi tiết bài học — trả về URL stream/document tùy `type`.

**Response 200 (video):**
```json
{
  "data": {
    "id": 5,
    "title": "Cài đặt môi trường",
    "type": "video",
    "stream_url": "/api/v1/media/10/stream?token=...",
    "duration": 720
  }
}
```

---

### POST `/lessons/{id}/progress`

Cập nhật tiến độ học.

**Request Body:**
```json
{ "watched_seconds": 350, "is_completed": false }
```

**Response 200:**
```json
{
  "data": {
    "is_completed": false,
    "watched_seconds": 350,
    "progress_percent": 48.6
  }
}
```

---

### GET `/courses/{slug}/progress`

Tổng quan tiến độ khóa học.

**Response 200:**
```json
{
  "data": {
    "total_lessons": 24,
    "completed_lessons": 10,
    "progress_percent": 41.7,
    "last_lesson": { "id": 5, "title": "Cài đặt môi trường", "slug": "..." }
  }
}
```

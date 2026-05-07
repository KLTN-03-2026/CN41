# API Reference — Quiz

Base URL: `http://localhost:8000/api/v1`

---

## Admin — Quản lý Quiz

### GET `/admin/quizzes`

**Middleware:** `auth:admin`, `permission:quizzes.view`

**Query params:** `search`, `status`, `lesson_id`, `page`, `per_page`

---

### POST `/admin/quizzes`

Tạo quiz thủ công.

**Request Body:**
```json
{
  "lesson_id": 5,
  "title": "Kiểm tra chương 1",
  "description": "Bài kiểm tra 10 câu, thời gian 15 phút",
  "max_attempts": 3,
  "time_limit": 15,
  "status": 1
}
```

**Validation:**
| Field | Rules |
|-------|-------|
| `lesson_id` | required, exists:lessons,id |
| `title` | required, string, max:255 |
| `max_attempts` | nullable, integer, min:1, max:10 |
| `time_limit` | nullable, integer, min:1 (phút) |
| `status` | nullable, in:0,1 |

---

### GET `/admin/quizzes/{id}`

Chi tiết quiz + danh sách questions (kèm correct_option).

---

### PATCH `/admin/quizzes/{id}`

Tất cả fields `sometimes`.

---

### DELETE `/admin/quizzes/{id}`

Soft delete quiz + questions.

---

### PATCH `/admin/quizzes/{id}/toggle-status`

---

### POST `/admin/quizzes/{id}/generate`

Sinh câu hỏi từ context bài học (sync, nhanh).

**Request Body:**
```json
{ "count": 5 }
```

**Response 200:** Quiz + questions mới sinh.

---

## Admin — AI Generation (Async)

### GET `/admin/lesson-quiz/{lessonId}`

Lấy quiz đang gắn với lesson (nếu có).

---

### POST `/admin/lesson-quiz/{lessonId}/generate`

Sinh quiz bất đồng bộ qua queue.

**Request Body:**
```json
{
  "count": 10,
  "source": "chapter",
  "pdf_lesson_id": 8,
  "custom_prompt": "Tập trung vào các khái niệm OOP"
}
```

| Field | Mô tả |
|-------|-------|
| `count` | Số câu hỏi (max: 20) |
| `source` | `chapter` = PDF từ lesson trong chương, `upload` = upload PDF mới |
| `pdf_lesson_id` | ID lesson chứa PDF (nếu source = chapter) |
| `pdf_file` | File PDF upload (nếu source = upload) |
| `custom_prompt` | Hướng dẫn thêm cho AI (tùy chọn) |

**Response 202:**
```json
{
  "success": true,
  "message": "Đang xử lý sinh câu hỏi, vui lòng chờ...",
  "data": { "job_id": 5, "status": "pending" }
}
```

---

### GET `/admin/lesson-quiz/jobs/{jobId}`

Poll trạng thái job sinh quiz.

**Response 200 — Đang xử lý:**
```json
{ "data": { "status": "processing" } }
```

**Response 200 — Hoàn thành:**
```json
{
  "data": {
    "status": "done",
    "quiz": { "id": 3, "title": "...", "total_questions": 10 },
    "questions": [
      {
        "id": 20,
        "question": "Đâu là đặc điểm của OOP?",
        "option_a": "Kế thừa",
        "option_b": "Biến toàn cục",
        "option_c": "Goto statement",
        "option_d": "Pointer arithmetic",
        "correct_option": "A",
        "order": 0
      }
    ]
  }
}
```

**Response 200 — Thất bại:**
```json
{ "data": { "status": "failed", "error": "Hệ thống AI đang bận. Vui lòng thử lại." } }
```

---

### GET `/admin/lesson-quiz/{lessonId}/chapter-pdfs`

Danh sách lessons có file PDF trong cùng chương, để chọn làm nguồn sinh quiz.

---

### PATCH `/admin/quiz-questions/{questionId}`

Sửa câu hỏi đã sinh.

**Request Body:**
```json
{
  "question": "Nội dung câu hỏi mới",
  "option_a": "...", "option_b": "...", "option_c": "...", "option_d": "...",
  "correct_option": "B"
}
```

---

### DELETE `/admin/quiz-questions/{questionId}`

Xóa câu hỏi khỏi quiz.

---

## Student (auth:api + email.verified)

### GET `/lessons/{lessonId}/quiz`

Lấy quiz của bài học. Chỉ trả quiz có `status = 1`.

**Response 200:**
```json
{
  "data": {
    "id": 3,
    "title": "Kiểm tra chương 1",
    "time_limit": 15,
    "max_attempts": 3,
    "attempts_used": 1,
    "questions": [
      {
        "id": 20,
        "question": "Đâu là đặc điểm của OOP?",
        "option_a": "Kế thừa",
        "option_b": "Biến toàn cục",
        "option_c": "Goto statement",
        "option_d": "Pointer arithmetic",
        "order": 0
      }
    ]
  }
}
```

> `correct_option` **không** được trả về cho student.

---

### POST `/quizzes/{id}/submit`

Nộp bài quiz.

**Request Body:**
```json
{
  "answers": {
    "20": "A",
    "21": "C",
    "22": "B"
  }
}
```

**Validation:**
| Field | Rules |
|-------|-------|
| `answers` | required, array |
| `answers.*` | in:A,B,C,D |

**Response 200:**
```json
{
  "data": {
    "score": 7,
    "total_questions": 10,
    "percentage": 70,
    "passed": true,
    "correct_answers": { "20": "A", "21": "C" }
  }
}
```

**Response 422:** Hết số lần thử (`max_attempts` đã đạt).

---

### GET `/quizzes/{id}/attempts`

Lịch sử làm bài của học viên hiện tại, sắp xếp mới nhất trước.

**Response 200:**
```json
{
  "data": [
    {
      "id": 8,
      "score": 7,
      "total_questions": 10,
      "percentage": 70,
      "passed": true,
      "completed_at": "2026-05-07T14:30:00Z"
    }
  ]
}
```

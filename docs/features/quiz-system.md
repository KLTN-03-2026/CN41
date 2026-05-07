# Hệ thống Quiz

## 1. Tổng quan

Quiz là tính năng đánh giá kiến thức gắn với từng bài học (lesson). Mỗi lesson có thể có **tối đa 1 quiz** (quan hệ 1:1). Học viên làm bài sau khi hoàn thành bài học, có giới hạn số lần thử và thời gian làm bài.

Điểm nổi bật: câu hỏi có thể được sinh **tự động bằng AI** (Google Gemini) từ tên/mô tả bài học hoặc từ **nội dung file PDF** của bài học — hoàn toàn bất đồng bộ qua Laravel Queue.

---

## 2. Cấu trúc dữ liệu

### Sơ đồ quan hệ

```
lessons (1) ──── (1) quizzes ──── (N) quiz_questions
                      │
                      └──── (N) quiz_attempts ──── students
                      │
                      └──── (N) quiz_generation_jobs
```

### Bảng `quizzes`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint | PK |
| `lesson_id` | bigint FK | Bài học chứa quiz (unique) |
| `title` | varchar(255) | Tiêu đề quiz |
| `description` | text nullable | Mô tả, hướng dẫn làm bài |
| `max_attempts` | tinyint default 3 | Số lần thử tối đa |
| `time_limit` | int nullable | Thời gian làm bài (phút), null = không giới hạn |
| `status` | tinyint default 1 | 0 = draft, 1 = published |
| `deleted_at` | timestamp | Soft delete |

### Bảng `quiz_questions`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint | PK |
| `quiz_id` | bigint FK | Quiz chứa câu hỏi (cascade delete) |
| `question` | text | Nội dung câu hỏi |
| `option_a` — `option_d` | text | 4 lựa chọn |
| `correct_option` | enum(A,B,C,D) | Đáp án đúng |
| `order` | int default 0 | Thứ tự hiển thị |

### Bảng `quiz_attempts`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint | PK |
| `quiz_id` | bigint FK | Quiz đã làm (cascade delete) |
| `student_id` | bigint FK | Học viên (cascade delete) |
| `score` | int | Số câu đúng |
| `total_questions` | int | Tổng số câu |
| `answers` | json | Đáp án học viên `{"<question_id>": "A", ...}` |
| `completed_at` | timestamp nullable | Thời điểm nộp bài |

### Bảng `quiz_generation_jobs`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint | PK |
| `lesson_id` | bigint FK | Bài học yêu cầu sinh quiz (cascade) |
| `status` | enum | `pending` / `processing` / `done` / `failed` |
| `payload` | json nullable | Dữ liệu đầu vào (context, count) |
| `result` | json nullable | Kết quả sau khi done |
| `error` | text nullable | Thông báo lỗi nếu failed |

---

## 3. API Endpoints

### Admin — Quản lý quiz

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/v1/admin/quizzes` | Danh sách quiz (phân trang) |
| POST | `/api/v1/admin/quizzes` | Tạo quiz thủ công |
| GET | `/api/v1/admin/quizzes/{id}` | Chi tiết quiz + questions |
| PATCH | `/api/v1/admin/quizzes/{id}` | Cập nhật quiz |
| DELETE | `/api/v1/admin/quizzes/{id}` | Xóa quiz |
| POST | `/api/v1/admin/quizzes/{id}/generate` | Sinh câu hỏi từ context (sync) |
| PATCH | `/api/v1/admin/quizzes/{id}/toggle-status` | Bật/tắt publish |

### Admin — AI Generation (async)

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/v1/admin/lesson-quiz/{lessonId}` | Lấy quiz của lesson |
| POST | `/api/v1/admin/lesson-quiz/{lessonId}/generate` | Sinh quiz async (queue) |
| GET | `/api/v1/admin/lesson-quiz/jobs/{jobId}` | Kiểm tra trạng thái job |
| GET | `/api/v1/admin/lesson-quiz/{lessonId}/chapter-pdfs` | Danh sách PDF trong chương |
| PATCH | `/api/v1/admin/quiz-questions/{questionId}` | Sửa câu hỏi |
| DELETE | `/api/v1/admin/quiz-questions/{questionId}` | Xóa câu hỏi |

### Student — Làm bài

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/v1/lessons/{lessonId}/quiz` | Lấy quiz (chỉ published) |
| POST | `/api/v1/quizzes/{id}/submit` | Nộp bài |
| GET | `/api/v1/quizzes/{id}/attempts` | Lịch sử làm bài |

Tất cả student routes yêu cầu `auth:api` + `email.verified`.

---

## 4. Luồng làm bài (Student)

```
Student mở bài học
  │
  │  GET /api/v1/lessons/{lessonId}/quiz
  ▼
QuizController::show()
  │
  ├── Kiểm tra quiz tồn tại và status = 1 (published)
  ├── Lấy danh sách questions (theo order)
  └── Trả về quiz info + questions (KHÔNG trả correct_option cho student)

Student làm bài (đếm ngược time_limit nếu có)
  │
  │  POST /api/v1/quizzes/{id}/submit
  │  Body: { "answers": {"1": "A", "2": "C", ...} }
  ▼
QuizController::submit()
  │
  ├── Đếm số lần đã thử: attempts.count()
  ├── So sánh với max_attempts → 422 nếu hết lượt
  │
  ├── Tính điểm:
  │     foreach câu hỏi:
  │       if answers[question.id] == question.correct_option → score++
  │
  ├── Lưu QuizAttempt:
  │     { quiz_id, student_id, score, total_questions, answers, completed_at }
  │
  └── Trả về: score, total, passed (score >= pass_score), correct_answers

Student xem lại:
  GET /api/v1/quizzes/{id}/attempts → danh sách lần thử (mới nhất trước)
```

---

## 5. Luồng sinh quiz bằng AI (async)

### 5.1 Tổng quan

Thay vì chặn HTTP request (Gemini API có thể mất 30–90s), hệ thống sử dụng **Laravel Queue** để xử lý bất đồng bộ:

```
Admin request → Dispatch Job → Return 202 ngay → Queue Worker xử lý → Admin poll kết quả
```

### 5.2 Hai nguồn sinh câu hỏi

| Nguồn | Mô tả | Khi dùng |
|-------|-------|---------|
| `chapter` | PDF từ lesson document trong cùng chương | Khi bài học có file PDF đính kèm |
| `upload` | Admin upload PDF trực tiếp khi generate | Khi muốn dùng tài liệu tùy chỉnh |
| (context) | Tên + mô tả lesson | Fallback khi không có PDF |

### 5.3 Flow chi tiết

```
Admin/Teacher
  │
  │  POST /api/v1/admin/lesson-quiz/{lessonId}/generate
  │  Body: {
  │    "count": 10,            // số câu hỏi (tối đa 20)
  │    "source": "chapter",    // hoặc "upload"
  │    "pdf_lesson_id": 5,     // nếu source = "chapter"
  │    "pdf_file": <file>,     // nếu source = "upload"
  │    "custom_prompt": "..."  // tùy chọn
  │  }
  ▼
QuizGenerateController::generate()
  │
  ├── Upload PDF tạm thời (nếu source = upload)
  │
  ├── Tạo/cập nhật QuizGenerationJob:
  │     { lesson_id, status: "pending", payload: {...} }
  │
  ├── Dispatch GenerateQuizJob vào queue "ai"
  │
  └── Return 202: { job_id, message: "Đang xử lý..." }
          │
          │  (background — queue worker)
          ▼
    GenerateQuizJob::handle()
          │
          ├── Cập nhật job status: "processing"
          │
          ├── [Nếu có PDF]
          │     ├── Trích xuất text từ PDF:
          │     │     - Dùng `pdftotext` (CLI) nếu có
          │     │     - Fallback: raw PDF parsing + decompress zlib streams
          │     └── AIQuizService::generateFromPdfText(text, count, context)
          │
          ├── [Không có PDF]
          │     └── AIQuizService::generateQuestions(lessonTitle + desc, count)
          │
          ├── DB::transaction():
          │     ├── Tạo hoặc cập nhật Quiz record
          │     ├── Xóa câu hỏi cũ (nếu có)
          │     └── Tạo QuizQuestions mới (bulk insert)
          │
          ├── Xóa file PDF tạm thời
          │
          └── Cập nhật job status: "done" / "failed" + error message

Admin poll:
  │
  │  GET /api/v1/admin/lesson-quiz/jobs/{jobId}
  └── { status: "done", quiz: {...}, questions: [...] }
```

### 5.4 AIQuizService — Tương tác Gemini API

```
AIQuizService::generateQuestions(context, count)
  │
  ├── Build prompt:
  │     - Yêu cầu JSON array thuần (không markdown)
  │     - {count} câu hỏi tiếng Việt
  │     - 4 lựa chọn A/B/C/D, 1 đáp án đúng
  │     - Phân bổ: 20% nhận biết, 50% thông hiểu, 30% vận dụng
  │
  ├── POST https://generativelanguage.googleapis.com/v1beta/
  │         models/gemini-2.0-flash:generateContent
  │     Config: temperature=0.4, maxOutputTokens=8192, timeout=90s
  │
  ├── [HTTP 429/503] → Fallback gemini-flash-lite-latest (retry once)
  ├── [HTTP 401/403] → Throw "API key không hợp lệ"
  ├── [ConnectionException] → Throw "Không thể kết nối AI"
  │
  ├── Parse JSON response:
  │     Strategy 1: regex extract `[...]` array
  │     Strategy 2: strip markdown code block, json_decode
  │
  └── normalizeQuestions(): đảm bảo đủ fields + strtoupper(correct_option)
```

**Prompt cho PDF** (`generateFromPdfText`): tương tự nhưng thêm nội dung tài liệu (tối đa 20.000 ký tự) vào prompt để AI sinh câu hỏi bám sát tài liệu.

### 5.5 Job configuration

```php
// GenerateQuizJob
public $timeout = 120;      // tối đa 2 phút
public $tries   = 1;        // không retry tự động
// WithoutOverlapping: chỉ 1 job AI chạy cùng lúc (singleton: 'ai_quiz_generate')
// Queue: 'ai' (cần php artisan queue:work --queue=ai,default)
```

---

## 6. Cấu hình môi trường

```env
GEMINI_API_KEY=your_gemini_api_key_here
```

Nếu thiếu key, mọi request generate sẽ throw exception ngay lập tức (không dispatch job).

---

## 7. Ví dụ response

### GET `/api/v1/lessons/{lessonId}/quiz` (student)

```json
{
  "success": true,
  "message": "Thành công",
  "data": {
    "id": 3,
    "title": "Kiểm tra chương 1",
    "description": "Bài kiểm tra 10 câu, thời gian 15 phút",
    "time_limit": 15,
    "max_attempts": 3,
    "attempts_used": 1,
    "questions": [
      {
        "id": 12,
        "question": "Đâu là đặc điểm của lập trình hướng đối tượng?",
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

### POST `/api/v1/quizzes/{id}/submit` — Response

```json
{
  "success": true,
  "message": "Nộp bài thành công",
  "data": {
    "score": 7,
    "total_questions": 10,
    "percentage": 70,
    "passed": true,
    "correct_answers": {
      "12": "A",
      "13": "C"
    }
  }
}
```

### POST `generate` — Response 202

```json
{
  "success": true,
  "message": "Đang xử lý sinh câu hỏi, vui lòng chờ...",
  "data": {
    "job_id": 5,
    "status": "pending"
  }
}
```

### GET `jobs/{jobId}` — Khi done

```json
{
  "success": true,
  "data": {
    "status": "done",
    "quiz": { "id": 3, "title": "...", "total_questions": 10 },
    "questions": [ { "id": 20, "question": "...", ... } ]
  }
}
```

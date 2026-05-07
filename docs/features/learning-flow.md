# Luồng học bài (Learning Flow)

## 1. Tổng quan

Sau khi enroll, học viên truy cập **LearnPage** — giao diện học fullscreen (không có ClientLayout). Trang này hỗ trợ 4 loại bài học: **video**, **document (PDF)**, **text**, và **quiz**. Tiến độ học được theo dõi tự động theo từng bài.

---

## 2. Cấu trúc dữ liệu tiến độ

### Bảng `lesson_progress`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `student_id` | bigint FK | |
| `lesson_id` | bigint FK | |
| `course_id` | bigint FK | |
| `is_completed` | boolean | Bài đã hoàn thành chưa |
| `watched_seconds` | int | Số giây video đã xem |
| `completed_at` | timestamp | Thời điểm hoàn thành |

---

## 3. Cấu trúc LearnPage

```
LearnPage.vue (fullscreen)
  │
  ├── LearnSidebar.vue          ← Danh sách sections/lessons, progress indicator
  │
  └── Content area (theo lesson.type):
        ├── [video]    LearnVideoPlayer.vue  ← video.js, range request, progress tracking
        ├── [document] LearnDocumentViewer.vue ← PDF viewer
        ├── [text]     Render HTML content
        └── [quiz]     LearnQuizPanel.vue   ← Quiz inline, hoặc redirect QuizPage
```

---

## 4. API Endpoints

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/v1/my-lessons/{courseSlug}` | Danh sách bài học + progress của khóa đã mua |
| GET | `/api/v1/my-lessons/{courseSlug}/{lessonSlug}` | Chi tiết bài học (video URL, document URL, content) |
| POST | `/api/v1/lessons/{id}/progress` | Cập nhật tiến độ |
| GET | `/api/v1/courses/{slug}/progress` | Tổng quan tiến độ khóa học |

Tất cả routes yêu cầu `auth:api` + `email.verified`.

---

## 5. Luồng mở bài học

```
Student click vào lesson trong LearnSidebar
  │
  │  GET /api/v1/my-lessons/{courseSlug}/{lessonSlug}
  ▼
LessonController::myLessonDetail()
  │
  ├── Kiểm tra student đã enroll course chưa
  │     └── [Chưa enroll] → 403 "Bạn chưa đăng ký khóa học này."
  │
  ├── [lesson.type == 'video']
  │     └── Trả về stream_url = "/api/v1/media/{video_id}/stream?token={studentToken}"
  │
  ├── [lesson.type == 'document']
  │     └── Trả về document_url = asset('storage/' + path) hoặc S3 presigned URL
  │
  ├── [lesson.type == 'text']
  │     └── Trả về content (HTML)
  │
  └── [lesson.type == 'quiz']
        └── Trả về quiz_id, redirect → QuizPage
```

---

## 6. Luồng xem video và tracking tiến độ

```
LearnVideoPlayer.vue
  │
  ├── Khởi tạo video.js player với src = stream_url
  │     stream_url dùng ?token= (vì <video> không gửi header được)
  │
  ├── Sự kiện 'timeupdate' (mỗi giây):
  │     watched_seconds = Math.floor(player.currentTime())
  │
  ├── Sự kiện 'ended' (video xem xong):
  │     → updateProgress({ is_completed: true, watched_seconds })
  │
  └── Sự kiện trước khi rời trang (beforeunload / visibilitychange):
        → updateProgress({ is_completed: false, watched_seconds })
          (lưu lại vị trí dừng)

POST /api/v1/lessons/{id}/progress
Body: { "watched_seconds": 245, "is_completed": false }
  │
  ▼
LessonController::updateProgress()
  │
  ├── Tìm hoặc tạo lesson_progress record (updateOrCreate)
  ├── [is_completed = true] → set completed_at = now()
  └── Return: { progress_percent, is_completed }
```

---

## 7. Luồng xem tổng quan tiến độ khóa học

```
GET /api/v1/courses/{slug}/progress
  │
  ▼
LessonController::courseProgress()
  │
  ├── Tổng số lessons published trong course
  ├── Số lessons đã completed (lesson_progress.is_completed = 1) của student
  └── Return:
      {
        total_lessons: 24,
        completed_lessons: 10,
        progress_percent: 41.7,
        last_lesson: { id, title, slug }  ← bài học gần nhất
      }
```

LearnSidebar hiển thị progress bar dựa trên `progress_percent`.

---

## 8. Video Streaming

Video được serve qua endpoint đặc biệt hỗ trợ **HTTP Range requests** (cần thiết cho seek trong video player):

```
GET /api/v1/media/{id}/stream
Authorization: Bearer <token>    ← hoặc ?token=<token> (cho <video src>)
Range: bytes=0-1048575           ← optional, browser tự gửi khi seek
  │
  ▼
UploadController::stream()
  │
  ├── Xác thực token (header hoặc query param)
  │
  ├── [Có Range header]
  │     └── Return 206 Partial Content + Content-Range header
  │
  ├── [Không có Range]
  │     └── Return 200 + toàn bộ file
  │
  ├── ETag / Cache-Control headers → browser cache file
  │
  └── [File trên S3]
        └── Tạo presigned URL → redirect 302 về S3 presigned URL
```

---

## 9. LearnSidebar — Hiển thị tiến độ

```
LearnSidebar.vue
  │
  ├── GET /api/v1/my-lessons/{courseSlug}
  │     → Danh sách tất cả sections + lessons kèm progress
  │
  └── Hiển thị:
        Section 1 — "Giới thiệu"
          ✅ Bài 1: Tổng quan (is_completed = true)
          ▶️ Bài 2: Cài đặt môi trường  ← bài đang xem
          🔒 Bài 3: Hello World
        Section 2 — "Cơ bản"
          🔒 ...

        Progress: 10/24 bài (41.7%)
```

---

## 10. Quiz trong bài học

Khi `lesson.type == 'quiz'`, học viên được chuyển đến **QuizPage** (fullscreen):

```
LearnPage: click lesson quiz
  │
  └── Router push → /lessons/{lessonId}/quiz

QuizPage.vue
  │
  ├── GET /api/v1/lessons/{lessonId}/quiz
  │     └── Lấy quiz + questions (không có correct_option)
  │
  ├── Đếm ngược time_limit (nếu có)
  │
  ├── Student chọn đáp án cho từng câu
  │
  ├── Nộp bài → POST /api/v1/quizzes/{id}/submit
  │
  └── Hiển thị kết quả: score, passed, review đáp án

→ Nút "Quay lại" → router back về LearnPage
→ Nút "Xem lịch sử" → /quizzes/{id}/history
```

Xem chi tiết hệ thống quiz tại [quiz-system.md](quiz-system.md).

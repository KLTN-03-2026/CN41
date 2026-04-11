# Test Module: Trang học (Learn Page)

> **Route BE:** `GET /api/v1/my-courses/{slug}/lessons`, `POST /api/v1/lessons/{id}/progress`
> **Page FE:** `/courses/:slug/learn`
> **Auth:** Bắt buộc đăng nhập + đã enroll khóa học

---

## QLCV — Việc cần làm

### Backend
- [ ] `myLessons`: trả về toàn bộ curriculum kèm `is_completed` cho từng lesson
- [ ] `myLessonDetail`: trả về nội dung lesson (video URL hoặc document URL) — chỉ trả nếu đã enroll
- [ ] `updateProgress`: `POST /api/v1/lessons/{id}/progress` — đánh dấu lesson hoàn thành
- [ ] `courseProgress`: `GET /api/v1/courses/{slug}/progress` — tổng % hoàn thành
- [ ] Stream video: `GET /api/v1/media/{id}/stream` — hỗ trợ Range header (seek video)
- [ ] Bảo vệ nội dung: lesson chưa enroll → 403

### Frontend
- [ ] `LearnPage.vue`: layout 2 cột — sidebar curriculum bên trái, player/content bên phải
- [ ] `LearnPage.vue`: video player với controls (play/pause/seek/fullscreen/volume)
- [ ] `LearnPage.vue`: document viewer (PDF embed hoặc download link)
- [ ] `LearnPage.vue`: đánh dấu lesson hoàn thành khi xem xong video (auto hoặc nút)
- [ ] `LearnPage.vue`: highlight lesson đang xem trong sidebar
- [ ] `LearnPage.vue`: nút "Bài tiếp theo" / "Bài trước"
- [ ] `LearnPage.vue`: progress tổng khóa học hiện trên header
- [ ] `LearnPage.vue`: sidebar collapse/expand trên mobile

---

## MODULE 1 — Truy cập trang học

### Test 1.1: Truy cập chưa đăng nhập

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Mở `/courses/laravel-co-ban/learn` không login | Redirect → `/login` |

### Test 1.2: Truy cập chưa enroll

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Login nhưng chưa enroll → mở learn page | Redirect về trang detail hoặc thông báo "Bạn chưa đăng ký khóa học này" |
| 2 | Network | BE trả về 403 |

### Test 1.3: Truy cập đã enroll ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Đã enroll → mở learn page | Trang load: sidebar curriculum + lesson đầu tiên tự động chọn |
| 2 | Network | `GET /api/v1/my-courses/{slug}/lessons` → 200 |
| 3 | Sidebar | Hiện tất cả sections và lessons, lesson đã hoàn thành có dấu tích |

---

## MODULE 2 — Video Player

### Test 2.1: Load video

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Chọn lesson video | Player hiện và video load |
| 2 | Network | `GET /api/v1/media/{id}/stream` với Range header |
| 3 | Controls | Play/Pause, thanh tiến trình, âm lượng, toàn màn hình |

### Test 2.2: Seek (tua video)

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Kéo thanh tiến trình | Video tua đến vị trí mới, không bị giật |
| 2 | Network | Range request mới gửi đến BE |

### Test 2.3: Đánh dấu hoàn thành

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Video xem đến cuối (hoặc click nút "Đánh dấu hoàn thành") | Lesson tích dấu check trong sidebar |
| 2 | Network | `POST /api/v1/lessons/{id}/progress` → 200 |
| 3 | Progress tổng | % tăng lên |

---

## MODULE 3 — Document Lesson

### Test 3.1: Load tài liệu

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Chọn lesson dạng document | Hiện PDF viewer hoặc nút "Tải xuống" |
| 2 | PDF viewer (nếu có) | Hiện nội dung PDF inline |
| 3 | Nút tải xuống | Download file về máy |

### Test 3.2: Đánh dấu hoàn thành tài liệu

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click "Đánh dấu hoàn thành" | Lesson tích check, progress tăng |
| 2 | Network | `POST /api/v1/lessons/{id}/progress` → 200 |

---

## MODULE 4 — Điều hướng bài học

### Test 4.1: Chuyển bài trong sidebar

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click lesson khác trong sidebar | Nội dung bên phải đổi sang lesson đó |
| 2 | Lesson đang xem | Highlight màu khác trong sidebar |

### Test 4.2: Nút "Bài tiếp theo"

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click "Bài tiếp theo" | Chuyển sang lesson kế tiếp trong cùng section |
| 2 | Hết section → bài tiếp theo | Chuyển sang bài đầu section tiếp theo |
| 3 | Bài cuối cùng của khóa | Nút "Bài tiếp theo" disable hoặc ẩn |

### Test 4.3: Nút "Bài trước"

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click "Bài trước" | Chuyển về lesson trước đó |
| 2 | Bài đầu tiên | Nút "Bài trước" disable hoặc ẩn |

### Test 4.4: Collapse/Expand section trong sidebar

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click tên section | Danh sách lesson thu gọn lại |
| 2 | Click lại | Mở rộng |

---

## MODULE 5 — Progress tổng khóa học

### Test 5.1: Hiện % tiến độ

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Đang học | Header hoặc sidebar hiện "X/Y bài đã hoàn thành (Z%)" |
| 2 | Hoàn thành thêm 1 bài | % tự cập nhật |

### Test 5.2: Hoàn thành toàn bộ khóa học

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Hoàn thành bài cuối | Toast/Banner "Chúc mừng! Bạn đã hoàn thành khóa học" |
| 2 | Progress | 100% |

---

## Checklist

| Test | Kết quả | Ghi chú |
|------|---------|---------|
| 1.1 Chưa đăng nhập | ⬜ | |
| 1.2 Chưa enroll | ⬜ | |
| 1.3 Truy cập đã enroll | ⬜ | |
| 2.1 Load video | ⬜ | |
| 2.2 Seek video | ⬜ | |
| 2.3 Đánh dấu hoàn thành | ⬜ | |
| 3.1 Load tài liệu | ⬜ | |
| 3.2 Hoàn thành tài liệu | ⬜ | |
| 4.1 Chuyển bài sidebar | ⬜ | |
| 4.2 Nút bài tiếp theo | ⬜ | |
| 4.3 Nút bài trước | ⬜ | |
| 4.4 Collapse section | ⬜ | |
| 5.1 % tiến độ | ⬜ | |
| 5.2 Hoàn thành toàn bộ | ⬜ | |

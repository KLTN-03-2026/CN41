# Test Module: Khóa học của tôi (My Courses)

> **Route BE:** `GET /api/v1/my-courses`
> **Page FE:** `/my-courses`
> **Auth:** Bắt buộc đăng nhập (guard: student)

---

## QLCV — Việc cần làm

### Backend
- [ ] `myCourses`: trả về danh sách khóa học student đã enroll (kèm progress %)
- [ ] `myCourses`: filter theo trạng thái học (đang học / đã hoàn thành)
- [ ] `myCourses`: trả về `last_accessed_lesson` để hiện "Tiếp tục học"
- [ ] API progress: `GET /api/v1/courses/{slug}/progress` — % hoàn thành, số lesson đã xong

### Frontend
- [ ] `MyCoursesPage.vue`: hiện danh sách khóa học đã enroll với progress bar
- [ ] `MyCoursesPage.vue`: nút "Tiếp tục học" → redirect đến lesson cuối đang học
- [ ] `MyCoursesPage.vue`: hiện empty state khi chưa enroll khóa nào
- [ ] `MyCoursesPage.vue`: filter "Đang học" / "Đã hoàn thành"

---

## MODULE 1 — Danh sách khóa học đã đăng ký

### Test 1.1: Truy cập chưa đăng nhập

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Mở `/my-courses` không login | Redirect → `/login?redirect=/my-courses` |
| 2 | Sau khi login | Redirect về `/my-courses` |

### Test 1.2: Danh sách khóa học đã enroll

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Login student đã enroll → `/my-courses` | Hiện danh sách khóa học với thumbnail, tên, progress bar |
| 2 | Network | `GET /api/v1/my-courses` → 200 |
| 3 | Progress bar | Hiện % dựa trên số lesson đã hoàn thành |

### Test 1.3: Empty state — Chưa đăng ký khóa nào

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Login student mới, chưa enroll | Hiện "Bạn chưa đăng ký khóa học nào" + nút "Khám phá khóa học" |
| 2 | Click "Khám phá" | Redirect → `/courses` |

### Test 1.4: Nút "Tiếp tục học"

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Khóa đang học dở | Hiện nút "Tiếp tục học" (không phải "Bắt đầu học") |
| 2 | Click | Redirect → `/courses/{slug}/learn` tại lesson cuối đang xem |
| 3 | Khóa chưa học bài nào | Nút "Bắt đầu học" → vào bài đầu tiên |

### Test 1.5: Khóa học đã hoàn thành

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Khóa học 100% | Progress bar xanh đầy + badge "Hoàn thành" |
| 2 | Nút | "Xem lại" thay vì "Tiếp tục học" |

### Test 1.6: Filter trạng thái

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Chọn "Đang học" | Chỉ hiện khóa progress < 100% |
| 2 | Chọn "Đã hoàn thành" | Chỉ hiện khóa progress = 100% |
| 3 | Chọn "Tất cả" | Hiện tất cả |

---

## Checklist

| Test | Kết quả | Ghi chú |
|------|---------|---------|
| 1.1 Chưa đăng nhập | ⬜ | |
| 1.2 Danh sách khóa học | ⬜ | |
| 1.3 Empty state | ⬜ | |
| 1.4 Tiếp tục học | ⬜ | |
| 1.5 Khóa đã hoàn thành | ⬜ | |
| 1.6 Filter trạng thái | ⬜ | |

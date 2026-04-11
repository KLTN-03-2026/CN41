# Test Module: Khóa học — Client (Public)

> **Route BE:** `GET /api/v1/courses`, `GET /api/v1/courses/{slug}`, `GET /api/v1/courses/{slug}/curriculum`
> **Pages FE:** `/courses`, `/courses/:slug`
> **Auth:** Không cần đăng nhập

---

## QLCV — Việc cần làm

### Backend
- [ ] `publicIndex`: filter theo `category_id`, `teacher_id`, `price` (free/paid), `level`, `search` — kiểm tra đủ các params
- [ ] `publicIndex`: sort theo `newest`, `popular`, `price_asc`, `price_desc`
- [ ] `publicIndex`: pagination — trả về `meta.total`, `meta.current_page`
- [ ] `publicShow`: trả về đầy đủ thông tin khóa học + teacher + category + rating
- [ ] `publicLessons` / `curriculum`: chỉ trả về lesson có `is_preview=true` cho user chưa đăng nhập
- [ ] Enroll free: `POST /api/v1/courses/{slug}/enroll-free` — cần auth:api

### Frontend
- [ ] `CoursesPage.vue`: filter bar (category, level, price)
- [ ] `CoursesPage.vue`: search debounce
- [ ] `CoursesPage.vue`: infinite scroll hoặc pagination
- [ ] `CourseDetailPage.vue`: hiện curriculum (section + lesson) — lesson chưa mua thì lock icon
- [ ] `CourseDetailPage.vue`: nút "Enroll miễn phí" / "Thêm vào giỏ" / "Vào học ngay"
- [ ] `CourseDetailPage.vue`: hiện thông tin giảng viên
- [ ] `HomePage.vue`: section "Khóa học nổi bật", "Khóa học mới nhất"

---

## MODULE 1 — Danh sách khóa học (`/courses`)

### Test 1.1: Load trang danh sách

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Mở `/courses` | Danh sách khóa học load, có thumbnail, tên, giảng viên, giá |
| 2 | Network | `GET /api/v1/courses` → 200 |
| 3 | Không login | Vẫn xem được bình thường |

### Test 1.2: Tìm kiếm khóa học

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Nhập "Laravel" vào ô tìm kiếm | Danh sách lọc theo từ khóa |
| 2 | Network | `GET /api/v1/courses?search=Laravel` |
| 3 | Xóa từ khóa | Về danh sách gốc |
| 4 | Nhập từ không khớp | Hiện "Không tìm thấy khóa học" |

### Test 1.3: Filter theo danh mục

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Chọn category "Lập trình" | Chỉ hiện khóa học thuộc category đó |
| 2 | Network | `GET /api/v1/courses?category_id=X` |
| 3 | Chọn "Tất cả" | Reset về danh sách đầy đủ |

### Test 1.4: Filter theo giá

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Chọn "Miễn phí" | Chỉ hiện khóa học price = 0 |
| 2 | Chọn "Có phí" | Chỉ hiện khóa học price > 0 |

### Test 1.5: Pagination / Load more

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Trang có nhiều khóa học | Hiện phân trang hoặc nút "Xem thêm" |
| 2 | Click trang 2 | Load thêm khóa học, không bị trùng |

---

## MODULE 2 — Chi tiết khóa học (`/courses/:slug`)

### Test 2.1: Load trang chi tiết

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click vào 1 khóa học | Trang chi tiết load với: thumbnail, tên, mô tả, giá, giảng viên |
| 2 | Network | `GET /api/v1/courses/{slug}` → 200 |
| 3 | Slug không tồn tại | 404 page |

### Test 2.2: Curriculum (mục lục khóa học)

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Xem mục lục | Hiện danh sách sections + lessons |
| 2 | Lesson có `is_preview=true` | Hiện icon play, có thể click xem |
| 3 | Lesson không preview | Hiện icon lock |
| 4 | Network | `GET /api/v1/courses/{slug}/curriculum` → 200 |

### Test 2.3: Xem preview lesson (chưa đăng nhập)

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click lesson preview | Modal hoặc page video player mở ra |
| 2 | Network | `GET /api/v1/courses/{slug}/preview-lesson/{lesson_slug}` → 200 |
| 3 | Click lesson bị lock | Prompt "Đăng nhập để học" hoặc "Mua khóa học" |

### Test 2.4: Enroll khóa học miễn phí (đã đăng nhập)

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Login student → vào khóa học free | Nút "Đăng ký học miễn phí" |
| 2 | Click nút | Loading → Toast "Đăng ký thành công!" |
| 3 | Network | `POST /api/v1/courses/{slug}/enroll-free` → 200 |
| 4 | Sau khi enroll | Nút đổi thành "Vào học ngay" |
| 5 | Click "Vào học ngay" | Redirect → `/courses/{slug}/learn` |

### Test 2.5: Enroll khóa học chưa đăng nhập

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Chưa login → Click "Đăng ký học" | Redirect → `/login?redirect=/courses/{slug}` |
| 2 | Sau khi login | Redirect về trang detail khóa học |

### Test 2.6: Khóa học có phí

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Xem khóa học có giá | Hiện giá + nút "Thêm vào giỏ hàng" |
| 2 | Click "Thêm vào giỏ" | Thêm vào cart, badge giỏ hàng tăng |

### Test 2.7: Đã enroll — truy cập lại trang detail

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Đã enroll → vào lại trang detail | Nút "Vào học ngay" thay vì "Đăng ký" |
| 2 | Network | BE trả về `is_enrolled: true` |

---

## Checklist

| Test | Kết quả | Ghi chú |
|------|---------|---------|
| 1.1 Load danh sách | ⬜ | |
| 1.2 Tìm kiếm | ⬜ | |
| 1.3 Filter category | ⬜ | |
| 1.4 Filter giá | ⬜ | |
| 1.5 Pagination | ⬜ | |
| 2.1 Load chi tiết | ⬜ | |
| 2.2 Curriculum | ⬜ | |
| 2.3 Preview lesson | ⬜ | |
| 2.4 Enroll miễn phí | ⬜ | |
| 2.5 Enroll chưa login | ⬜ | |
| 2.6 Khóa học có phí | ⬜ | |
| 2.7 Đã enroll truy cập lại | ⬜ | |

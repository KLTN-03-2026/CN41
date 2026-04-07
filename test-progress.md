# 🧪 Hướng dẫn Test Chi Tiết — Theo Từng Module

> Mỗi test case ghi rõ: **hành động** → **kết quả mong đợi** → **cách verify**.
> Dùng DevTools (F12) → **Network tab** để xem request/response.

---

## 📋 Bước 0: Chuẩn bị

### Chạy Backend
```bash
cd ~/DATN/e-learning/e-learning-backend
php artisan serve
# → http://localhost:8000
```

### Chạy Frontend
```bash
cd ~/DATN/e-learning/e-learning-frontend
npm run dev
# → http://localhost:5173
```

### Mở DevTools
- F12 → Tab **Network** (tick "Preserve log")
- F12 → Tab **Console** (xem lỗi JS)
- F12 → Tab **Application** → **Local Storage** (xem token)

> [!IMPORTANT]
> Luôn mở Network tab khi test. Nếu thấy request trả 404, ghi lại URL thực tế để so sánh với BE route.

---

## 🔐 MODULE 1: AUTH — Admin

### Test 1.1: Admin Login — Form trống

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Mở `http://localhost:5173/admin/login` | Trang login hiển thị: logo, form email/password, nút "Đăng nhập" |
| 2 | Nhấn "Đăng nhập" mà không nhập gì | Hiện lỗi inline: "Vui lòng nhập email" + "Vui lòng nhập mật khẩu" (**client-side**) |
| 3 | Kiểm tra Network tab | **Không có request** nào được gửi (VeeValidate chặn trước) |

### Test 1.2: Admin Login — Email sai format

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Nhập email: `abc` | Lỗi inline: "Email không đúng định dạng" |
| 2 | Nhập email: `abc@` | Lỗi inline: "Email không đúng định dạng" |
| 3 | Nhập email hợp lệ nhưng password trống | Chỉ lỗi password, email OK |

### Test 1.3: Admin Login — Sai mật khẩu

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Nhập: `admin@example.com` / `wrong_password` | Alert đỏ hiện: "Email hoặc mật khẩu không đúng." |
| 2 | Network tab | Request `POST /api/v1/admin/auth/login` → Response **401** |
| 3 | Kiểm tra nút | Nút hết loading, form vẫn giữ email đã nhập |

### Test 1.4: Admin Login — Email không tồn tại

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Nhập: `notexist@test.com` / `123456` | Alert: "Email hoặc mật khẩu không đúng." → **401** |

### Test 1.5: Admin Login — Password quá ngắn (< 6 ký tự)

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Nhập: `admin@example.com` / `12345` | BE trả **422** với error: "Mật khẩu phải có ít nhất 6 ký tự." |
| 2 | FE hiển thị | Alert đỏ hiện message lỗi từ server |

### Test 1.6: Admin Login — Đăng nhập thành công ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Nhập: `admin@example.com` / `password` (hoặc password đúng của seed) | Loading spinner hiện lên |
| 2 | Khi thành công | Redirect → `/admin/dashboard` |
| 3 | Network tab | `POST /api/v1/admin/auth/login` → **200**, response có `token` + `user` |
| 4 | Application → Local Storage | Key `adminToken` xuất hiện với giá trị token |
| 5 | Header góc phải | Hiển thị tên admin (VD: "Admin") |

### Test 1.7: Admin Session Persist

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Sau khi login thành công, refresh trang (F5) | Vẫn ở `/admin/dashboard`, KHÔNG bị redirect về login |
| 2 | Mở tab mới → `/admin/courses` | Truy cập được (token vẫn còn trong localStorage) |

### Test 1.8: Admin Route Guard — Chưa login

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Xóa `adminToken` trong localStorage (Application tab → xóa key) | — |
| 2 | Truy cập `/admin/dashboard` | Redirect → `/admin/login` |
| 3 | Truy cập `/admin/courses` | Redirect → `/admin/login` |
| 4 | Truy cập `/admin/categories` | Redirect → `/admin/login` |

### Test 1.9: Admin Login — Đã login rồi vào lại trang login

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Đã login admin → truy cập `/admin/login` | Redirect ngay → `/admin/dashboard` (guard `requiresGuest`) |

---

## 🎓 MODULE 2: AUTH — Student

### Test 2.1: Student Register — Form trống

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Mở `/register` | Trang đăng ký hiện ra: 4 fields (tên, email, password, confirm) |
| 2 | Nhấn "Đăng ký" không nhập gì | Lỗi inline tất cả fields (**client-side**) |
| 3 | Network tab | Không có request |

### Test 2.2: Student Register — Tên quá ngắn

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Nhập tên: `A` (1 ký tự) | Lỗi: "Họ tên tối thiểu 2 ký tự" |
| 2 | Nhập tên: `AB` (2 ký tự) | Không lỗi |

### Test 2.3: Student Register — Email sai format

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Email: `test` | Lỗi: "Email không đúng định dạng" |
| 2 | Email: `test@` | Lỗi: "Email không đúng định dạng" |
| 3 | Email: `test@test.com` | Không lỗi |

### Test 2.4: Student Register — Password quá ngắn

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Password: `1234567` (7 ký tự) | Lỗi: "Mật khẩu tối thiểu 8 ký tự" |
| 2 | Password: `12345678` (8 ký tự) | Không lỗi |

### Test 2.5: Student Register — Confirm password không khớp

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Password: `12345678`, Confirm: `12345679` | Lỗi: "Mật khẩu xác nhận không khớp" |
| 2 | Password: `12345678`, Confirm: `12345678` | Không lỗi |

### Test 2.6: Student Register — Đăng ký thành công ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Nhập: `Test User` / `test1@test.com` / `12345678` / `12345678` | Loading → toast "Đăng ký thành công!" |
| 2 | Network | `POST /api/v1/auth/register` → **201** |
| 3 | Response body | Có `token` + `student` object |
| 4 | localStorage | `studentToken` xuất hiện |
| 5 | Redirect | Về `/` (trang chủ) |

### Test 2.7: Student Register — Email đã tồn tại (trùng) ⚠️

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Đăng ký lại với email `test1@test.com` (vừa đăng ký ở Test 2.6) | Alert đỏ hiện lỗi server |
| 2 | Network | `POST /api/v1/auth/register` → **422** |
| 3 | Response body | `errors.email: ["Email đã được sử dụng."]` |
| 4 | FE hiển thị | Alert hiện message: "Email đã được sử dụng." |

> [!NOTE]
> Đây là test quan trọng để kiểm tra FE xử lý lỗi 422 từ server đúng cách.

### Test 2.8: Student Login — Sai mật khẩu

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Logout trước (xóa `studentToken` trong localStorage) | — |
| 2 | Mở `/login` → Nhập `test1@test.com` / `wrongpass` | Alert: "Email hoặc mật khẩu không đúng." |
| 3 | Network | `POST /api/v1/auth/login` → **401** |

### Test 2.9: Student Login — Đăng nhập thành công ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Nhập `test1@test.com` / `12345678` | Toast "Đăng nhập thành công!" → redirect `/` |
| 2 | localStorage | `studentToken` xuất hiện |

### Test 2.10: Student Login — Redirect sau login

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Xóa `studentToken` | — |
| 2 | Truy cập `/my-courses` (cần auth) | Redirect → `/login?redirect=/my-courses` |
| 3 | Đăng nhập thành công | Redirect → `/my-courses` (KHÔNG phải `/`) |

### Test 2.11: Student Route Guard — Chưa login

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Xóa `studentToken` | — |
| 2 | Truy cập `/my-courses` | Redirect → `/login?redirect=/my-courses` |
| 3 | Truy cập `/cart` | Redirect → `/login?redirect=/cart` |
| 4 | Truy cập `/profile` | Redirect → `/login?redirect=/profile` |
| 5 | Truy cập `/courses` (public) | OK — không cần auth |
| 6 | Truy cập `/courses/some-slug` (public) | OK — không cần auth |

### Test 2.12: Student Login — Đã login vào lại trang login/register

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Đã login student → `/login` | Redirect → `/` |
| 2 | Đã login student → `/register` | Redirect → `/` |

---

## 🗂️ MODULE 3: CATEGORIES — Admin CRUD

> Cần login Admin trước.

### Test 3.1: Danh sách Categories — Trang trống

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Mở `/admin/categories` | Trang load, bảng hiển thị (có thể trống nếu chưa seed) |
| 2 | Network | `GET /api/v1/admin/categories` → **200** |
| 3 | Nếu trống | Hiện message "Không có danh mục" hoặc bảng rỗng |

### Test 3.2: Tạo Category — Form trống

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click "Thêm danh mục" | Modal mở |
| 2 | Nhấn Submit mà không nhập gì | Lỗi validation (client hoặc server 422) |

### Test 3.3: Tạo Category — Thành công ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Tên: `Lập trình` | Slug tự động: `lap-trinh` |
| 2 | Parent: `-- Không có --` (root) | — |
| 3 | Submit | Toast thành công, modal đóng, bảng refresh |
| 4 | Network | `POST /api/v1/admin/categories` → **201** |
| 5 | Bảng | Row mới xuất hiện: "Lập trình", slug `lap-trinh`, Cấp: Gốc |

### Test 3.4: Tạo Category con

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click "Thêm danh mục" | Modal |
| 2 | Tên: `PHP`, Parent: chọn `Lập trình` | Slug: `php` |
| 3 | Submit | Thành công |
| 4 | Bảng | Row "PHP" hiện ra, indent `└`, Cấp: Cấp 1 |

### Test 3.5: Tạo Category — Slug trùng

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Tạo category mới với tên `Lập trình` (trùng slug `lap-trinh`) | Server trả **422** |
| 2 | Response | `errors.slug: ["slug đã được sử dụng."]` |
| 3 | FE | Hiện lỗi inline tại field slug |

### Test 3.6: Tạo Category — Tên tiếng Việt + ký tự đặc biệt

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Tên: `Thiết kế đồ họa` | Slug auto: `thiet-ke-do-hoa` |
| 2 | Tên: `C++/C#` | Slug auto: `c-c` hoặc tương tự (normalize) |
| 3 | Submit | Thành công (nếu slug unique) |

### Test 3.7: Sửa Category

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click icon sửa trên row "PHP" | Modal mở, form điền sẵn: name=PHP, slug=php, parent=Lập trình |
| 2 | Sửa tên → `PHP & MySQL` | Slug có thể auto hoặc giữ nguyên |
| 3 | Submit | Toast thành công, bảng cập nhật |
| 4 | Network | `PUT /api/v1/admin/categories/{id}` → **200** |

### Test 3.8: Sửa Category — Đổi slug trùng

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Sửa "PHP & MySQL" → slug đổi thành `lap-trinh` (đã tồn tại) | **422** lỗi slug trùng |

### Test 3.9: Xóa Category

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click icon xóa trên 1 row | Confirm dialog hiện "Bạn có chắc muốn xóa?" |
| 2 | Click "Hủy" | Dialog đóng, không xóa |
| 3 | Click "Xóa" lại → Click "Xác nhận" | Toast thành công, row biến mất |
| 4 | Network | `DELETE /api/v1/admin/categories/{id}` → **200** |

### Test 3.10: Phân trang

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Tạo > 15 categories | Phân trang xuất hiện bên dưới bảng |
| 2 | Click trang 2 | Bảng load dữ liệu trang 2 |
| 3 | Network | `GET /api/v1/admin/categories?page=2` → **200** |

---

## 📚 MODULE 4: COURSES — Admin

> [!WARNING]
> **Vấn đề API prefix**: Theo endpoint.md, Courses dùng prefix `/api/admin/courses` (KHÔNG có `v1`).
> Nhưng FE axios baseURL là `/api/v1`, nên FE sẽ gọi `/api/v1/admin/courses`.
> Nếu test thấy **404**, đây là nguyên nhân — cần sửa API prefix.

### Test 4.1: Danh sách Courses

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Mở `/admin/courses` | Bảng hiển thị: thumbnail, tên, giảng viên, giá, status badge |
| 2 | Network | `GET .../admin/courses` → **200** |
| 3 | **Nếu 404** | → prefix sai, cần sửa `coursesApi.js` |

### Test 4.2: Search debounce

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Gõ "Laravel" vào search | Đợi 400ms → tự động filter |
| 2 | Network | Request có `?search=Laravel` |
| 3 | Xóa search | Bảng trở về hiển thị tất cả |

### Test 4.3: Filter Level + Status

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Chọn Level: "Cơ bản" | Chỉ hiện courses level=beginner |
| 2 | Chọn Status: "Nháp" | Chỉ hiện courses status=0 |
| 3 | Bỏ filter | Hiện tất cả |

### Test 4.4: Toggle Status

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click badge status (VD: "Nháp") trên 1 row | Badge đổi sang "Đã đăng" (hoặc ngược lại) |
| 2 | Network | `PATCH .../admin/courses/{id}/toggle-status` → **200** |

### Test 4.5: Tạo khóa học mới

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click "Thêm khóa học" | Navigate → `/admin/courses/create` |
| 2 | Form hiện: tên, slug, mô tả, teacher, category, level, giá | — |
| 3 | Gõ tên: `Vue.js từ A-Z` | Slug auto: `vue-js-tu-a-z` |
| 4 | Chọn teacher, category, level, nhập giá | — |
| 5 | Submit | Toast thành công |
| 6 | Network | `POST .../admin/courses` → **201** |
| 7 | Redirect | → `/admin/courses/{id}/edit` (để mở tab Bài giảng) |

### Test 4.6: Tạo khóa học — Thiếu field bắt buộc

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Submit form trống | Lỗi validation: tên, slug, teacher_id, price, level bắt buộc |
| 2 | Network | `POST ...` → **422** hoặc bị client validate chặn |

### Test 4.7: Tạo khóa học — Slug trùng

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Tạo course với slug giống course đã có | **422**: "slug đã được sử dụng." |

### Test 4.8: Tạo khóa học — sale_price > price

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Nhập price: 100000, sale_price: 200000 | **422**: "Giá khuyến mãi phải nhỏ hơn hoặc bằng giá gốc." |

### Test 4.9: Edit khóa học

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Quay lại `/admin/courses` → Click icon sửa | Navigate → `/admin/courses/{id}/edit` |
| 2 | Form điền sẵn data | Tên, slug, mô tả, teacher... đã fill |
| 3 | Có 2 tabs: "Thông tin" + "Bài giảng" | Tab thứ 2 hiện component LessonsManager |
| 4 | Sửa tên → Submit | Toast thành công |

### Test 4.10: Xóa khóa học

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click icon xóa trên 1 row | Confirm dialog |
| 2 | Xác nhận | Soft delete → row biến mất, toast thành công |

---

## 📝 MODULE 5: LESSONS — Admin (LessonsManager)

> Test trong trang `/admin/courses/{id}/edit` → Tab "Bài giảng".

### Test 5.1: Danh sách bài giảng

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Mở edit course → Click tab "Bài giảng" | Bảng bài giảng (hoặc trống nếu chưa có) |
| 2 | Network | `GET .../admin/courses/{id}/lessons` → **200** |

### Test 5.2: Thêm bài giảng — Thành công

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click "Thêm bài giảng" | Modal mở |
| 2 | Title: `Giới thiệu khóa học`, Type: Video, Order: 0, Status: Published | — |
| 3 | Submit | Toast thành công, bài xuất hiện trong bảng |
| 4 | Network | `POST .../admin/courses/{id}/lessons` → **201** |

### Test 5.3: Thêm bài giảng — Thiếu title

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Submit modal mà không nhập title | Lỗi validation: "title là bắt buộc" |

### Test 5.4: Toggle status bài giảng

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click badge status trên row bài giảng | Status đổi (Draft ↔ Published) |
| 2 | Network | `PATCH .../admin/lessons/{id}/toggle-status` → **200** |

### Test 5.5: Sửa bài giảng

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click icon sửa | Modal load data cũ: title, type, content, order... |
| 2 | Sửa title → Submit | Cập nhật thành công |

### Test 5.6: Xóa bài giảng

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click icon xóa → Confirm | Bài biến mất, toast thành công |
| 2 | Network | `DELETE .../admin/lessons/{id}` → **200** |

---

## 🌐 MODULE 6: PUBLIC — Courses (Client)

> Không cần login.

### Test 6.1: Danh sách khóa học public

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Mở `/courses` | Grid card khóa học (4 cột desktop) |
| 2 | Loading | Skeleton loading 8 cards |
| 3 | Mỗi card | Thumbnail, level badge, tên, giảng viên, giá |
| 4 | Network | `GET /api/v1/courses` → **200** |
| 5 | Chỉ thấy | Courses có `status=1` (Published) |

### Test 6.2: Search + Filter

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Gõ tên khóa học | Debounce 400ms → filter |
| 2 | Chọn level | Chỉ hiện courses level đó |
| 3 | Chọn category | Chỉ hiện courses category đó |

### Test 6.3: Chi tiết khóa học

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click vào 1 card | Navigate → `/courses/{slug}` |
| 2 | Layout 2 cột | Main: breadcrumb + title + mô tả + lessons. Sidebar: thumbnail + giá + CTA |
| 3 | Danh sách bài giảng | Bài preview: icon "Xem thử". Bài khác: icon khóa 🔒 |
| 4 | Network | `GET /api/v1/courses/{slug}` + `GET /api/v1/courses/{slug}/lessons` |

### Test 6.4: Nút CTA — Chưa login

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click "Thêm vào giỏ hàng" (chưa login) | Có thể redirect login hoặc thêm vào cart localStorage |

### Test 6.5: Responsive

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Thu hẹp browser → mobile (< 768px) | Grid 1 cột, sidebar xuống dưới |
| 2 | Tablet (768-1024px) | Grid 2 cột |

---

## 🎓 MODULE 7: MY COURSES — Client Auth

> Cần login Student.

### Test 7.1: Chưa mua khóa nào

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Login student mới → `/my-courses` | Empty state: "Bạn chưa có khóa học nào" + link → `/courses` |
| 2 | Network | `GET /api/v1/my-courses` → **200**, data: `[]` |

### Test 7.2: Có khóa học đã mua

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Login student đã mua khóa → `/my-courses` | Grid 3 cột: thumbnail, tên, progress bar (%) |
| 2 | Progress bar | Hiển thị % đúng (hoặc 0% nếu chưa học) |
| 3 | Nút bấm | "Bắt đầu học" (nếu 0%) hoặc "Tiếp tục học" (nếu > 0%) |

---

## 📺 MODULE 8: LEARN PAGE — Client Auth

> Cần login Student + đã mua khóa học.

### Test 8.1: Truy cập không login

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Xóa studentToken → truy cập `/courses/{slug}/learn` | Redirect → `/login?redirect=/courses/{slug}/learn` |

### Test 8.2: Layout

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Login → truy cập `/courses/{slug}/learn` | Layout full-screen 2 cột |
| 2 | Sidebar (w-80) | Tên khóa, progress bar, danh sách bài |
| 3 | Main | Video player / nội dung bài giảng |
| 4 | Sidebar highlight | Bài đang xem được highlight |

### Test 8.3: Video auto-save progress

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Play video | Onended phát ra sau 10 giây |
| 2 | Network (mỗi 10s) | `POST /api/v1/lessons/{id}/progress` với `watched_seconds` |

### Test 8.4: Mark complete

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click "Đánh dấu hoàn thành" | Request complete → icon ✅ trên sidebar |
| 2 | Progress bar overall | Cập nhật % |

### Test 8.5: Navigation prev/next

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click "Bài tiếp theo" | Load bài tiếp, sidebar di chuyển highlight |
| 2 | Click "Bài trước" | Quay lại bài trước |
| 3 | Bài đầu tiên | Nút "Bài trước" bị disabled |
| 4 | Bài cuối cùng | Nút "Bài tiếp" bị disabled |

### Test 8.6: Responsive mobile

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Thu nhỏ browser < 768px | Sidebar ẩn, có nút toggle |
| 2 | Click toggle | Sidebar hiện ra overlay |

---

## ⚠️ Vấn đề đã biết — Đã fix

### ✅ Vấn đề 1: API Prefix (ĐÃ FIX)

**Vấn đề**: BE modules (Categories, Courses, Lessons, Teachers, Upload) dùng prefix `api` thay vì `api/v1` → FE gọi 404.

**Fix**: Sửa `RouteServiceProvider.php` của 5 modules BE thêm `v1` vào prefix + xóa `v1` trùng lặp trong `routes/api.php`.

### ✅ Vấn đề 2: Axios interceptor redirect 401 trên login (ĐÃ FIX)

**Fix**: Thêm `AUTH_PATHS` whitelist trong `src/plugins/axios.js`.

### ✅ Vấn đề 3: Zod v4 undefined error (ĐÃ FIX)

**Fix**: Thêm `z.string({ error: '...' })` cho tất cả auth form schemas.

### 🟡 Vấn đề 4: LoginPage/RegisterPage dùng lucide icons

**Chi tiết**: Import từ `lucide-vue-next` thay vì `@/icons`. Không ảnh hưởng chức năng.

---

## 📊 Checklist tóm tắt

| Module | Tổng test cases | Trạng thái |
|--------|----------------|------------|
| Auth Admin (Login) | 9 cases | ✅ Passed |
| Auth Student (Register + Login) | 12 cases | ✅ Passed |
| Categories Admin (CRUD) | 11 cases | ✅ Passed |
| Courses Admin | 10 cases | ⬜ Chưa test |
| Lessons Admin | 6 cases | ⬜ Chưa test |
| Public Courses | 5 cases | ⬜ Chưa test |
| My Courses | 2 cases | ⬜ Chưa test |
| Learn Page | 6 cases | ⬜ Chưa test |
| **Tổng** | **61 cases** | **32/61 passed** |

> Cập nhật: 07/04/2026 — Module 1, 2, 3 đã test xong (32 cases, 0 failed, 10 bugs fixed).

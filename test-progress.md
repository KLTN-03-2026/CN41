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

### Test 4.1: Danh sách Courses

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Mở `/admin/courses` | Bảng hiển thị: checkbox chọn, thumbnail, tên + level badge, giảng viên, giá (gốc + sale), học viên, status badge |
| 2 | Network | `GET /api/v1/admin/courses` → **200** |
| 3 | Tabs | Có 2 tab: "Đang hoạt động" và "Thùng rác" (có badge số) |

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
| 2 | Network | `PATCH /api/v1/admin/courses/{id}/toggle-status` → **200** |
| 3 | Toast | Hiện "Đã đăng khóa học" hoặc "Đã chuyển về nháp" |

### Test 4.5: Tạo khóa học mới

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click "Thêm khóa học" | Navigate → `/admin/courses/create` |
| 2 | Form hiện: tên, slug, mô tả, teacher, category, level, trạng thái, giá, sale price, thumbnail | — |
| 3 | Gõ tên: `Vue.js từ A-Z` | Slug auto: `vue-js-tu-a-z` |
| 4 | Chọn teacher, category, level, nhập giá | — |
| 5 | Submit | Toast thành công |
| 6 | Network | `POST /api/v1/admin/courses` → **201** |
| 7 | Redirect | → `/admin/courses` (danh sách) |

### Test 4.6: Tạo khóa học — Thiếu field bắt buộc

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Submit form trống | Lỗi validation client-side: tên, slug, teacher bắt buộc |
| 2 | Network | **Không có request** (client validate chặn) |

### Test 4.7: Tạo khóa học — Slug trùng

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Tạo course với slug giống course đã có | **422**: "slug đã được sử dụng." |

### Test 4.8: Tạo khóa học — sale_price > price

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Nhập price: 100000, sale_price: 200000 | Lỗi client-side: "Giá khuyến mãi phải nhỏ hơn hoặc bằng giá gốc" |
| 2 | Network | **Không có request** (client validate chặn) |

### Test 4.9: Tạo khóa học — Slug validation format

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Nhập slug: `UpperCase` hoặc `has space` | Lỗi: "Slug chỉ chứa chữ thường, số và dấu gạch ngang" |
| 2 | Nhập slug: `valid-slug-123` | Không lỗi |

### Test 4.10: Thumbnail Upload

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Trong form tạo/sửa course, click vùng dropzone | File picker mở |
| 2 | Chọn file ảnh JPG/PNG/WebP < 5MB | Preview hiện ngay (blob URL), progress bar chạy |
| 3 | Network | `POST /api/v1/admin/upload/image` → **200** |
| 4 | Upload xong | Toast "Upload ảnh thành công", ảnh hiện preview final |
| 5 | Click nút (X) trên ảnh | Ảnh bị xóa, dropzone trở lại |

### Test 4.11: Thumbnail Upload — File quá lớn

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Chọn file > 5MB | Lỗi: "File quá lớn. Tối đa 5MB." |
| 2 | Network | **Không có request** (client validate) |

### Test 4.12: Thumbnail Upload — Định dạng sai

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Chọn file .gif hoặc .pdf | Lỗi: "Định dạng không hỗ trợ. Chỉ JPG, PNG, WebP." |

### Test 4.13: Thumbnail Drag & Drop

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Kéo file ảnh từ desktop vào dropzone | Dropzone highlight (border xanh), thả → upload bắt đầu |
| 2 | Kéo file không phải ảnh | Lỗi: "Vui lòng chọn file ảnh (JPG, PNG, WebP)" |

### Test 4.14: Edit khóa học

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Quay lại `/admin/courses` → Click icon sửa (bút chì) | Navigate → `/admin/courses/{id}/edit` |
| 2 | Form điền sẵn data | Tên, slug, mô tả, teacher, category, level, giá... đã fill |
| 3 | Có 2 tabs: "Thông tin" + "Nội dung" | Tab "Nội dung" hiện component SectionsLessonsManager |
| 4 | Slug bị khóa | Có nút "🔒 Mở khóa" để cho phép sửa slug |
| 5 | Sửa tên → Submit | Toast "Cập nhật khóa học thành công" |

### Test 4.15: Edit khóa học — Category giữ giá trị

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Edit course đã có category → kiểm tra dropdown category | Category đã chọn đúng giá trị (nhiều cấp hiện indent `—`) |
| 2 | Đổi category → Submit → Mở lại edit | Category mới được giữ đúng |

### Test 4.16: Shortcut "Nội dung" từ danh sách

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Ở danh sách courses, click icon sách (bên cạnh icon bút chì) | Navigate → `/admin/courses/{id}/edit?tab=lessons` |
| 2 | Trang edit mở | Tab "Nội dung" tự động active (không phải "Thông tin") |

### Test 4.17: Xóa khóa học (Soft Delete)

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click icon xóa (thùng rác) trên 1 row | Confirm dialog: "Bạn có chắc muốn xóa?" + note "Khóa học sẽ được chuyển vào thùng rác" |
| 2 | Click "Hủy" | Dialog đóng, không xóa |
| 3 | Click "Xóa" | Toast thành công, row biến mất, badge "Thùng rác" tăng lên |
| 4 | Network | `DELETE /api/v1/admin/courses/{id}` → **200** |

### Test 4.18: Tab Thùng rác

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click tab "Thùng rác" | Chuyển sang bảng trashed: border đỏ, warning banner |
| 2 | Bảng hiển thị | Thumbnail (mờ), tên, giảng viên, giá, thời gian xóa |
| 3 | Network | `GET /api/v1/admin/courses/trashed` → **200** |
| 4 | Có search box | Tìm kiếm trong thùng rác (debounce 400ms) |

### Test 4.19: Khôi phục khóa học từ thùng rác

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Ở tab Thùng rác, click icon khôi phục (mũi tên xoay) | Toast thành công: "Đã khôi phục..." |
| 2 | Row biến mất khỏi thùng rác | Badge count giảm |
| 3 | Chuyển tab "Đang hoạt động" | Course vừa khôi phục xuất hiện lại |
| 4 | Network | `POST /api/v1/admin/courses/{id}/restore` → **200** |

### Test 4.20: Xóa vĩnh viễn

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Ở tab Thùng rác, click icon xóa vĩnh viễn (thùng rác) | Confirm dialog với cảnh báo đỏ: "Hành động này không thể hoàn tác!" |
| 2 | Click "Xóa vĩnh viễn" | Toast thành công, row biến mất |
| 3 | Network | `DELETE /api/v1/admin/courses/{id}/force-delete` → **200** |

### Test 4.21: Bulk Select — Chọn tất cả

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click checkbox header (chọn tất cả) | Tất cả rows được highlight xanh, floating bar xuất hiện |
| 2 | Floating bar hiện | "Đã chọn N khóa học" + nút: Đăng, Nháp, Xóa, Bỏ chọn |
| 3 | Click checkbox header lần nữa | Bỏ chọn tất cả, floating bar biến mất |

### Test 4.22: Bulk Select — Chọn từng row

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click checkbox row 1, row 3 | 2 rows highlight, floating bar: "Đã chọn 2 khóa học" |
| 2 | Header checkbox | Hiện trạng thái indeterminate (—) |

### Test 4.23: Bulk Toggle Status — Đăng

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Chọn 2 courses → Click "Đăng" trong floating bar | Toast: "Đã cập nhật 2 khóa học" |
| 2 | Status badges | Cả 2 đổi thành "Đã đăng" |
| 3 | Floating bar | Biến mất (selectedIds cleared) |

### Test 4.24: Bulk Toggle Status — Nháp

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Chọn 2 courses → Click "Nháp" | Toast: "Đã cập nhật 2 khóa học" |
| 2 | Status badges | Cả 2 đổi thành "Nháp" |

### Test 4.25: Bulk Delete

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Chọn 3 courses → Click "Xóa" trong floating bar | Confirm dialog: "Bạn có chắc muốn xóa 3 khóa học đã chọn?" |
| 2 | Click "Xóa tất cả" | Toast: "Đã xóa 3 khóa học", bảng refresh |
| 3 | Network | `DELETE /api/v1/admin/courses/bulk-delete` với body `{ ids: [...] }` |
| 4 | Thùng rác badge | Tăng lên 3 |

### Test 4.26: Bulk Restore (Thùng rác)

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Tab Thùng rác → chọn tất cả → Click "Khôi phục" | Toast thành công, thùng rác trống |
| 2 | Network | `POST /api/v1/admin/courses/bulk-restore` → **200** |

### Test 4.27: Bulk Force Delete (Thùng rác)

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Tab Thùng rác → chọn 2 → Click "Xóa vĩnh viễn" | Confirm dialog với cảnh báo đỏ |
| 2 | Click "Xóa vĩnh viễn tất cả" | Toast thành công |
| 3 | Network | `DELETE /api/v1/admin/courses/bulk-force-delete` → **200** |

### Test 4.28: Phân trang Courses

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Tạo > 15 courses | Phân trang xuất hiện: "1–15 / N khóa học" |
| 2 | Click trang 2 | Bảng load dữ liệu trang 2, nút trang 2 highlight |
| 3 | Network | `GET /api/v1/admin/courses?page=2` → **200** |

---

## 📝 MODULE 5: SECTIONS & LESSONS — Admin (SectionsLessonsManager)

> Test trong trang `/admin/courses/{id}/edit` → Tab "Nội dung".
> Hoặc truy cập trực tiếp từ shortcut icon sách trong danh sách.

### Test 5.1: Hiển thị trang nội dung — Trống

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Mở edit course → Click tab "Nội dung" | Tiêu đề header đổi thành **"Nội dung khóa học"** (thay vì "Chỉnh sửa khóa học"), subtitle hiện tên khóa học. |
| 2 | Component hiện | Header "0 chương · 0 bài giảng", 2 nút "Thêm bài giảng" + "Thêm chương" |
| 3 | Network | `GET /api/v1/admin/courses/{id}/sections` + `GET /api/v1/admin/courses/{id}/lessons` → **200** |
| 4 | Nếu không có nội dung | Empty state: "Chưa có nội dung. Hãy thêm chương hoặc bài giảng." |

### Test 5.2: Thêm chương (Section) — Thành công ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click "Thêm chương" | Modal mở: Tiêu đề*, Mô tả, Thứ tự, Trạng thái |
| 2 | Nhập: `Chương 1: Giới thiệu`, Thứ tự: 0, Trạng thái: Đã đăng | — |
| 3 | Submit | Toast "Tạo chương thành công", modal đóng |
| 4 | Network | `POST /api/v1/admin/courses/{id}/sections` → **201** |
| 5 | Giao diện | Card chương mới xuất hiện: số thứ tự, tên, badge trạng thái, "0 bài giảng" |
| 6 | Header | Cập nhật "1 chương · 0 bài giảng" |

### Test 5.3: Thêm chương — Thiếu tiêu đề

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click "Thêm chương" → Submit không nhập gì | Lỗi: "Vui lòng nhập tiêu đề" (**client-side**) |
| 2 | Network | **Không có request** |

### Test 5.4: Sửa chương

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click icon bút chì trên section header | Modal mở: form điền sẵn title, description, order, status |
| 2 | Sửa tên → Submit | Toast "Cập nhật chương thành công" |
| 3 | Network | `PUT /api/v1/admin/sections/{id}` → **200** |

### Test 5.5: Toggle trạng thái chương

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click icon toggle (⊖) trên section header | Badge đổi: "Nháp" ↔ "Đã đăng" |
| 2 | Network | `PATCH /api/v1/admin/sections/{id}/toggle-status` → **200** |

### Test 5.6: Xóa chương

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click icon thùng rác trên section | Confirm dialog: "Xác nhận xóa chương" + cảnh báo "Các bài giảng sẽ chuyển thành Chưa phân chương" |
| 2 | Click "Hủy" | Dialog đóng |
| 3 | Click "Xóa" | Toast "Xóa chương thành công", section biến mất |
| 4 | Network | `DELETE /api/v1/admin/sections/{id}` → **200** |
| 5 | Bài giảng trong chương | Chuyển sang nhóm "Chưa phân chương" (viền cam, dashed border) |

### Test 5.7: Sắp xếp chương (Reorder)

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Tạo 3 chương: Chương 1, Chương 2, Chương 3 | — |
| 2 | Click mũi tên lên (▲) trên Chương 2 | Chương 2 đổi lên vị trí 1 |
| 3 | Click mũi tên xuống (▼) trên Chương 2 (bây giờ ở vị trí 1) | Về lại vị trí 2 |
| 4 | Network | `POST /api/v1/admin/sections/reorder` với body `{ orders: [...] }` |
| 5 | Chương đầu tiên | Không có nút ▲ |
| 6 | Chương cuối cùng | Không có nút ▼ |

### Test 5.8: Expand/Collapse chương

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click vào header chương (vùng tên/mũi tên) | Nội dung bài giảng toggle hiện/ẩn |
| 2 | Mũi tên | Xoay 90° khi mở, 0° khi đóng |
| 3 | Mặc định lần đầu load | Tất cả chương tự động mở rộng |

### Test 5.9: Thêm bài giảng — Vào chương cụ thể ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click icon (+) trên header chương cụ thể | Modal mở, dropdown "Chương" đã chọn sẵn chương đó |
| 2 | Nhập: Title: `Giới thiệu khóa học`, Type: Video, Order: 0, Status: Đã đăng | — |
| 3 | Submit | Toast "Tạo bài giảng thành công" |
| 4 | Network | `POST /api/v1/admin/courses/{id}/lessons` → **201**, body có `section_id` |
| 5 | Bài giảng xuất hiện | Trong chương vừa chọn, hiện: STT, tên, badge type (Video), preview icon, thời lượng, status |

### Test 5.10: Thêm bài giảng — Không gán chương

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click nút "Thêm bài giảng" ở header (KHÔNG phải icon trên chương) | Modal mở, dropdown "Chương" = "— Chưa phân chương —" |
| 2 | Submit | Bài giảng xuất hiện ở nhóm "Chưa phân chương" (viền cam) |

### Test 5.11: Thêm bài giảng — Thiếu tiêu đề

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Mở modal → Submit không nhập title | Lỗi: "Vui lòng nhập tiêu đề" |
| 2 | Network | **Không có request** |

### Test 5.12: Thêm bài giảng — Các loại type

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Type: "Video" → Submit | Badge xanh dương "Video" |
| 2 | Type: "Tài liệu" → Submit | Badge cam "Tài liệu" |
| 3 | Type: "Văn bản" → Submit | Badge xám "Văn bản" |

### Test 5.13: Thêm bài giảng — Checkbox "Cho xem thử"

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Tick "Cho xem thử (preview)" → Submit | Bài giảng hiện icon 👁️ |
| 2 | Không tick → Submit | Không có icon 👁️ |

### Test 5.14: Sửa bài giảng

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click icon bút chì trên row bài giảng | Modal load data: section, title, type, content, order, duration, preview, status |
| 2 | Sửa title → Submit | Toast "Cập nhật bài giảng thành công" |
| 3 | Network | `PUT /api/v1/admin/lessons/{id}` → **200** |

### Test 5.15: Sửa bài giảng — Chuyển chương

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Edit bài ở "Chương 1" → đổi dropdown thành "Chương 2" | — |
| 2 | Submit → Reload | Bài biến mất khỏi Chương 1, xuất hiện ở Chương 2 |

### Test 5.16: Toggle trạng thái bài giảng

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click badge status (VD: "Nháp") trên row bài | Badge đổi sang "Đã đăng" (hoặc ngược lại) |
| 2 | Network | `PATCH /api/v1/admin/lessons/{id}/toggle-status` → **200** |

### Test 5.17: Xóa bài giảng

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click icon thùng rác trên row bài | Confirm dialog: "Xác nhận xóa bài giảng" |
| 2 | Click "Xóa" | Toast "Xóa bài giảng thành công", row biến mất |
| 3 | Network | `DELETE /api/v1/admin/lessons/{id}` → **200** |

### Test 5.18: Sắp xếp bài giảng (Reorder)

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Tạo 3 bài trong 1 chương | — |
| 2 | Click ▲ trên bài 2 | Bài 2 lên vị trí 1 |
| 3 | Click ▼ trên bài 1 (bây giờ ở vị trí 2) | Bài 1 xuống vị trí 3 |
| 4 | Network | `POST /api/v1/admin/lessons/reorder` → **200** |
| 5 | Bài đầu | Không có nút ▲ |
| 6 | Bài cuối | Không có nút ▼ |

### Test 5.19: Nhóm "Chưa phân chương"

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Tạo bài giảng không gán chương (section_id = null) | Nhóm "Chưa phân chương" xuất hiện: viền cam dashed |
| 2 | Hiển thị | "N bài giảng chưa gán vào chương nào" |
| 3 | Click để expand | Danh sách bài giảng orphan hiện ra |
| 4 | Có thể sửa/xóa/toggle status | Tương tự bài giảng trong chương |

### Test 5.20: Thời lượng bài giảng

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Tạo bài với duration: 300 | Hiện "5:00" (format mm:ss) |
| 2 | Tạo bài với duration: 3661 | Hiện "1:01:01" (format h:mm:ss) |
| 3 | Tạo bài không nhập duration | Hiện "—" |

### Test 5.21: Bulk Assign Section — Phân chương hàng loạt ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Chọn nhiều bài giảng (ở các chương khác nhau hoặc bài chưa phân chương) | Floating bar xuất hiện, có nút "Phân chương" (màu tím). |
| 2 | Click "Phân chương" | Modal "Phân chương hàng loạt" hiện ra với dropdown danh sách chương. |
| 3 | Chọn 1 chương (VD: Chương 2) → Xác nhận | Toast thông báo thành công, các bài giảng đã chọn chuyển vào Chương 2. |
| 4 | Network | `POST /api/v1/admin/lessons/bulk-action` với body `{ ids: [...], action: "assign-section", section_id: N }` → **200**. |

### Test 5.22: Bulk Assign Section — Bỏ gán chương hàng loạt

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Chọn nhiều bài giảng đang nằm trong các chương | Floating bar → nút "Phân chương". |
| 2 | Chọn dropdown: "— Bỏ phân chương (Chưa gán) —" → Xác nhận | Các bài giảng đã chọn chuyển xuống nhóm "Chưa phân chương". |
| 3 | Network | Request có `section_id: null` → **200**. |

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

### Test 6.2: Search + Filter ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Gõ tên khóa học | Debounce 400ms → filter |
| 2 | Chọn level | Chỉ hiện courses level đó |
| 3 | Chọn category | Chỉ hiện courses category đó |

### Test 6.3: Chi tiết khóa học ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|-------------------|
| 1 | Click vào 1 card | Navigate → `/courses/{slug}` |
| 2 | Layout 2 cột | Main: breadcrumb + title + mô tả + lessons. Sidebar: thumbnail + giá + CTA |
| 3 | Danh sách bài giảng | Bài preview: icon "Xem thử". Bài khác: icon khóa 🔒 |
| 4 | Network | `GET /api/v1/courses/{slug}` + `GET /api/v1/courses/{slug}/lessons` |
| 5 | Khóa học liên quan | Hiển thị các khóa học cùng danh mục, click vào thì reload dữ liệu |

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

### ✅ Vấn đề 4: Category không giữ giá trị khi edit course (ĐÃ FIX)

**Vấn đề**: Dropdown category không hiển thị đúng khi edit course vì BE trả `categories` array (many-to-many), nhưng FE chỉ bind 1 `category_id`.

**Fix**: FE lấy `c.categories?.[0]?.id` khi load, gửi `category_ids: [form.category_id]` khi submit.

### 🟡 Vấn đề 5: LoginPage/RegisterPage dùng lucide icons

**Chi tiết**: Import từ `lucide-vue-next` thay vì `@/icons`. Không ảnh hưởng chức năng.

---

## 📊 Checklist tóm tắt

| Module | Tổng test cases | Trạng thái |
|--------|----------------|------------|
| Auth Admin (Login) | 9 cases | ✅ Passed |
| Auth Student (Register + Login) | 12 cases | ✅ Passed |
| Categories Admin (CRUD) | 10 cases | ✅ Passed |
| Courses Admin (CRUD + Bulk + Trash + Upload) | 28 cases | ⬜ Chưa test |
| Sections & Lessons Admin | 20 cases | ⬜ Chưa test |
| Public Courses | 5 cases | ⬜ Chưa test |
| My Courses | 2 cases | ⬜ Chưa test |
| Learn Page | 6 cases | ⬜ Chưa test |
| **Tổng** | **92 cases** | **31/92 passed** |

> Cập nhật: 08/04/2026 — Module 1, 2, 3 đã test xong (31 cases, 0 failed, 10 bugs fixed).
> Module 4 đã mở rộng đáng kể: thêm Bulk actions, Trash/Restore, Thumbnail upload (28 cases).
> Module 5 thay đổi hoàn toàn: từ LessonsManager → SectionsLessonsManager (Sections + Lessons hierarchy, 20 cases).

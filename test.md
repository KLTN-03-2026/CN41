# 🧪 Kết quả Test Frontend — E-Learning

> Cập nhật lần cuối: 07/04/2026
> Tester: Văn Thành

---

## 🔐 MODULE 1: AUTH — Admin Login ✅ PASSED

| # | Test Case | Kết quả | Ghi chú |
|---|-----------|---------|---------|
| 1.1 | Form trống → hiện validate tiếng Việt | ✅ Pass | Fix: Zod v4 cần `z.string({ error })` |
| 1.2 | Email sai format → lỗi inline | ✅ Pass | |
| 1.3 | Sai mật khẩu → alert "Email hoặc mật khẩu không đúng" | ✅ Pass | Fix: axios interceptor bỏ qua redirect 401 cho auth endpoints |
| 1.4 | Email không tồn tại → alert lỗi, không reload | ✅ Pass | Fix: axios interceptor |
| 1.5 | Password < 6 ký tự → validate "ít nhất 6 ký tự" | ✅ Pass | Fix: thêm `.min(6)` vào Zod schema |
| 1.6 | Đăng nhập thành công → redirect `/admin/dashboard` | ✅ Pass | |
| 1.7 | Session persist — refresh vẫn giữ login | ✅ Pass | |
| 1.8 | Route guard — chưa login bị redirect | ✅ Pass | |
| 1.9 | Guard guest — đã login vào `/admin/login` bị redirect dashboard | ✅ Pass | |

### Bugs đã fix trong module này:
1. **Axios interceptor redirect 401 trên login** — `src/plugins/axios.js` — interceptor bắt 401 của login request → reload trang. Fix: thêm `AUTH_PATHS` whitelist, skip redirect cho auth endpoints.
2. **Zod v4 lỗi "Invalid input: expected string, received undefined"** — `AdminLoginPage.vue` — Zod v4 reject `undefined` với message tiếng Anh. Fix: thêm `z.string({ error: 'Message tiếng Việt' })`.
3. **Thiếu validate min(6) password** — `AdminLoginPage.vue` — chỉ check "không trống". Fix: thêm `.min(6, 'Mật khẩu phải có ít nhất 6 ký tự')`.

---

## 🎓 MODULE 2: AUTH — Student Register + Login ✅ PASSED

| # | Test Case | Kết quả | Ghi chú |
|---|-----------|---------|---------|
| 2.1 | Register form trống → validate tiếng Việt | ✅ Pass | Fix: Zod v4 `z.string({ error })` |
| 2.2 | Tên < 2 ký tự → lỗi | ✅ Pass | |
| 2.3 | Email sai format → lỗi | ✅ Pass | |
| 2.4 | Password < 8 ký tự → lỗi "tối thiểu 8 ký tự" | ✅ Pass | Fix: `.refine()` → `.superRefine()` |
| 2.5 | Confirm password không khớp → lỗi | ✅ Pass | |
| 2.6 | Đăng ký thành công → toast + redirect `/` | ✅ Pass | |
| 2.7 | Email trùng → alert "Email đã được sử dụng" | ✅ Pass | |
| 2.8 | Student login sai password → alert lỗi | ✅ Pass | |
| 2.9 | Student login thành công → redirect | ✅ Pass | |
| 2.10 | Redirect sau login (query.redirect) | ✅ Pass | |
| 2.11 | Route guard — chưa login bị redirect `/login` | ✅ Pass | |
| 2.12 | Guard guest — đã login vào `/login` bị redirect `/` | ✅ Pass | |

### Bugs đã fix trong module này:
1. **Zod v4 "Invalid input" trên RegisterPage** — `RegisterPage.vue` — cùng lỗi Zod v4. Fix: thêm `z.string({ error })` cho tất cả fields.
2. **`.refine()` nuốt lỗi field-level** — `RegisterPage.vue` — Zod v4 `.refine()` ảnh hưởng error reporting. Fix: chuyển sang `.superRefine()`.
3. **LoginPage thiếu min(6) password** — `LoginPage.vue` — Fix: thêm `.min(6)` + `z.string({ error })`.

---

## 🗂️ MODULE 3: CATEGORIES — Admin CRUD ✅ PASSED

| # | Test Case | Kết quả | Ghi chú |
|---|-----------|---------|---------|
| 3.1 | Vào trang `/admin/categories` → hiển thị danh sách | ✅ Pass | Fix: backend route prefix `api` → `api/v1` |
| 3.2 | Danh sách hiển thị dạng cây cha-con | ✅ Pass | Chuyển từ `index` (flat) sang `flatTree` API |
| 3.3 | Click mũi tên ▶ → expand/collapse danh mục con | ✅ Pass | Mặc định thu gọn, chỉ hiện danh mục cha |
| 3.4 | Nút "Mở rộng tất cả" / "Thu gọn tất cả" | ✅ Pass | |
| 3.5 | Tìm kiếm danh mục → lọc + highlight kết quả | ✅ Pass | Giữ chuỗi cha (ancestor chain) khi search |
| 3.6 | Tạo category — Tên tiếng Việt + ký tự đặc biệt (`C++`, `C#`) | ✅ Pass | Fix: autoSlug chuyển `+` → `plus`, `#` → `sharp` |
| 3.7 | Tạo category — slug tự sinh từ tên | ✅ Pass | |
| 3.8 | Tạo category — chọn danh mục cha | ✅ Pass | Dropdown dạng flat-tree với indentation |
| 3.9 | Sửa category — form điền sẵn dữ liệu cũ | ✅ Pass | |
| 3.10 | Xóa category — confirm modal + toast thành công | ✅ Pass | |
| 3.11 | Modal overlay đè lên sidebar | ✅ Pass | Fix: z-index `z-50` → `z-[100000]` |

### Bugs đã fix trong module này:
1. **Backend 404 — Route prefix thiếu `v1`** — `Modules/Categories/app/Providers/RouteServiceProvider.php` — prefix `api` thay vì `api/v1`, frontend gọi `/api/v1/admin/categories` → 404. Fix: thêm `v1` vào prefix cho 5 modules (Categories, Course, Lessons, Teachers, Upload).
2. **Modal overlay không đè sidebar** — `CategoriesPage.vue` — Modal `z-50` bị sidebar `z-[99999]` đè. Fix: tăng modal lên `z-[100000]`.
3. **AutoSlug mất ký tự đặc biệt** — `CategoriesPage.vue` — `C++` → slug `c` (++ bị xóa). Fix: chuyển `++` → `plus-plus`, `#` → `sharp`, `&` → `and` trước khi strip.
4. **Danh sách flat không thể hiện cây** — `CategoriesPage.vue` — Chuyển từ `categoriesApi.index()` (flat paginated) sang `categoriesApi.flatTree()` với tree view expand/collapse + search/filter.

## 📚 MODULE 4: COURSES — Admin ⬜ Chưa test

## 📝 MODULE 5: LESSONS — Admin ⬜ Chưa test

## 🌐 MODULE 6: PUBLIC — Courses ⬜ Chưa test

## 🎓 MODULE 7: MY COURSES ⬜ Chưa test

## 📺 MODULE 8: LEARN PAGE ⬜ Chưa test

---

## 📊 Tổng kết

| Module | Cases | Passed | Failed | Bugs fixed |
|--------|-------|--------|--------|------------|
| Auth Admin | 9 | 9 | 0 | 3 |
| Auth Student | 12 | 12 | 0 | 3 |
| Categories | 11 | 11 | 0 | 4 |
| Courses Admin | 10 | — | — | — |
| Lessons Admin | 6 | — | — | — |
| Public Courses | 5 | — | — | — |
| My Courses | 2 | — | — | — |
| Learn Page | 6 | — | — | — |
| **Tổng** | **61** | **32** | **0** | **10** |

---

## 📝 File đã sửa trong quá trình test

| File | Thay đổi |
|------|----------|
| `src/plugins/axios.js` | Thêm AUTH_PATHS whitelist, skip redirect 401 cho auth endpoints |
| `src/pages/auth/AdminLoginPage.vue` | Zod v4 `z.string({ error })` + `.min(6)` password |
| `src/pages/auth/LoginPage.vue` | Zod v4 `z.string({ error })` + `.min(6)` password |
| `src/pages/auth/RegisterPage.vue` | Zod v4 `z.string({ error })` + `.refine()` → `.superRefine()` |
| `src/pages/admin/CategoriesPage.vue` | Tree view + expand/collapse + search/filter + fix autoSlug + fix modal z-index |
| `src/pages/admin/CoursesPage.vue` | Fix modal z-index `z-50` → `z-[100000]` |
| `Modules/Categories/app/Providers/RouteServiceProvider.php` | Fix prefix `api` → `api/v1` |
| `Modules/Course/app/Providers/RouteServiceProvider.php` | Fix prefix `api` → `api/v1` |
| `Modules/Lessons/app/Providers/RouteServiceProvider.php` | Fix prefix `api` → `api/v1` |
| `Modules/Teachers/app/Providers/RouteServiceProvider.php` | Fix prefix `api` → `api/v1` |
| `Modules/Upload/app/Providers/RouteServiceProvider.php` | Fix prefix `api` → `api/v1` |
| `Modules/Categories/routes/api.php` | Xóa prefix `v1` trùng lặp |
| `Modules/Course/routes/api.php` | Xóa prefix `v1` trùng lặp |
| `Modules/Lessons/routes/api.php` | Xóa prefix `v1` trùng lặp |
| `Modules/Teachers/routes/api.php` | Xóa prefix `v1` trùng lặp |

---
description: "FE 4 — Quiz Pages (Admin + Client) + Dashboard Admin (ApexCharts)"
---

# FE 4 — Advanced Features

> **Phase F4** — Quiz (AI), Dashboard charts.
> Prerequisite: Backend Module Quiz + Dashboard API đã có.

---

## Task F4.1 — Admin QuizPage.vue

Backend:
- `POST /api/v1/admin/quizzes/generate` (upload PDF → AI sinh câu hỏi)
- `GET /api/v1/admin/quizzes?lesson_id=X`
- `PUT /api/v1/admin/quizzes/{id}`

### Tạo `src/api/quizApi.js`:
```js
adminGenerate(lessonId, file)   → POST with FormData
adminGetByLesson(lessonId)      → GET
adminUpdate(id, data)           → PUT
// Client:
getByLesson(lessonId)           → GET /api/v1/lessons/{id}/quiz
submitAnswer(quizId, data)      → POST /api/v1/quizzes/{id}/submit
```

### Tạo `src/pages/admin/QuizPage.vue`:
- Chọn bài giảng (dropdown)
- Upload PDF → `POST generate-quiz`
- Hiển thị câu hỏi từ AI response
- Cho phép sửa câu hỏi / đáp án trước khi lưu
- Nút "Lưu quiz" → PUT update

---

## Task F4.2 — Client QuizPage.vue

- Route: thêm vào sau trang `LearnPage` hoặc route riêng
- Hiện danh sách câu hỏi (radio options)
- Submit → hiện kết quả (đúng/sai cho từng câu + tổng điểm)
- Icons: `CheckCircle2` (đúng, green), `XCircle` (sai, red)

---

## Task F4.3 — Admin DashboardPage.vue (hoàn chỉnh)

> Cài thêm: `npm i vue3-apexcharts apexcharts`

Backend:
- `GET /api/v1/admin/dashboard/stats` → { total_students, total_courses, total_revenue, new_orders_this_month }
- `GET /api/v1/admin/dashboard/revenue?period=monthly&year=2026` → [{ label, revenue }]
- `GET /api/v1/admin/dashboard/top-courses?limit=5` → [{name, total_students, revenue}]

### Tạo `src/api/dashboardApi.js`:
```js
getStats()                → GET /api/v1/admin/dashboard/stats
getRevenue(params)        → GET /api/v1/admin/dashboard/revenue
getTopCourses(limit)      → GET /api/v1/admin/dashboard/top-courses
```

### Cập nhật `src/pages/admin/DashboardPage.vue`:

#### 1. Row 4 StatCard:
| Stat | Icon | Màu |
|------|------|-----|
| Tổng học viên | Users | blue |
| Tổng khóa học | BookOpen | green |
| Doanh thu tháng này | DollarSign | amber |
| Đơn hàng mới | ShoppingBag | purple |

> Dùng `formatCurrency` cho doanh thu.

#### 2. Biểu đồ doanh thu theo tháng:
```js
import VueApexCharts from 'vue3-apexcharts'
// type: 'area', màu primary-600
```

#### 3. Bảng top 5 khóa học:
- Thumbnail + tên + số học viên + doanh thu
- Dùng `formatCurrency` cho doanh thu

#### 4. Loading skeleton khi fetch data

---

## Đăng ký ApexCharts trong `main.js`:
```js
import VueApexCharts from 'vue3-apexcharts'
app.use(VueApexCharts)
```

---

## ✅ Kiểm tra hoàn thành

- [ ] Admin quiz: upload PDF → gen quiz → sửa → lưu
- [ ] Client quiz: làm quiz → submit → hiện kết quả
- [ ] Dashboard: 4 StatCard hiện data thực
- [ ] Dashboard: biểu đồ doanh thu render đúng
- [ ] Dashboard: bảng top 5 courses
- [ ] Loading skeleton

Tick F4.1 → F4.3 trong `QLCV/QLCV-FE.md`.

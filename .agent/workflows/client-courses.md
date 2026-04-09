---
description: "FE 2.4–2.5 — Client: Danh sách Courses + Chi tiết Course"
---

# FE 2.4–2.5 — Client Courses Pages

> **Phase F2 (phần Client)** — Tạo trang danh sách và chi tiết khóa học.
> Prerequisite: Backend GET /api/v1/courses + GET /api/v1/courses/{slug} đã có.

---

## Context

```
Stack: Vue 3, Tailwind, Flowbite Vue, lucide-vue-next, @vueuse/core.
Backend:
  - GET /api/v1/courses   → params: page, per_page, category_id, search, sort
  - GET /api/v1/courses/{slug}   → course detail với teacher, categories
  - GET /api/v1/courses/{slug}/lessons → danh sách bài giảng
```

---

## Task F2.4 — Client CoursesPage.vue

Tạo `src/pages/client/CoursesPage.vue`:

### 1. Grid course cards (3 cột desktop / 2 tablet / 1 mobile)

**CourseCard** (`src/components/client/CourseCard.vue`):
- Thumbnail ảnh + badge danh mục (`FwbBadge` từ flowbite-vue)
- Tên khóa học (truncate 2 dòng)
- Tên giảng viên (`User` icon từ lucide)
- Rating stars (nếu có)
- Số bài giảng (`PlayCircle` icon) + thời lượng (`Clock` icon)
- Giá: nếu `sale_price` → gạch `price` + nổi bật `sale_price` (text-primary-600 font-bold)
- Nếu `price = 0` → badge "Miễn phí"
- Dùng `formatCurrency` từ `utils/formatCurrency.js`
- Click → `router.push('/courses/' + course.slug)`

### 2. Filter

- **Search input** với debounce 500ms (dùng `useDebounce` từ @vueuse/core)
- **Filter danh mục** (gọi `GET /api/v1/categories` — dùng `FwbAccordion` hoặc select)
- **Sort dropdown** (`FwbDropdown`): Mới nhất / Phổ biến / Giá tăng / Giá giảm

### 3. Loading + Empty state

- **Loading:** 6 skeleton cards (`AppSkeleton.vue`)
- **Empty:** Search icon + "Không tìm thấy khóa học phù hợp"

### 4. Pagination

- Dùng `AppPagination` component

---

## Task F2.5 — Client CourseDetailPage.vue

Tạo `src/pages/client/CourseDetailPage.vue`:

### Layout 2 cột (desktop):

**Cột trái (~65%):**
- Tên, mô tả, danh mục (`FwbBadge`), thông tin giảng viên
- Danh sách bài giảng dùng `FwbAccordion` (nhóm theo section/parent_id)
  - Icon `PlayCircle` (trial/unlocked) hoặc `Lock` (locked) từ lucide
  - Bài `is_trial` → click mở `FwbModal` chứa video player
  - Bài chưa mua → `FwbTooltip` "Mua khóa học để xem"
  - Bài đã mua → click navigate `/courses/{slug}/learn?lesson={id}`

**Cột phải (~35%) — sticky card:**
- Thumbnail, giá, nút hành động, stats
- **Giá:** `sale_price` → `<span class="line-through text-gray-400">price</span>` + sale_price bold
- `price = 0` → `FwbBadge` "Miễn phí" màu green

### Nút hành động:
- Chưa đăng nhập → "Đăng nhập để mua" → redirect `/login?redirect=current`
- Đăng nhập, chưa mua → "Thêm vào giỏ" (`cartStore.addItem`) + "Mua ngay"
- Đã mua → "Vào học ngay" → `/courses/{slug}/learn`

### Breadcrumb:
- Dùng Flowbite Breadcrumb: Trang chủ / Khóa học / {tên}

---

## ✅ Kiểm tra hoàn thành

- [ ] Danh sách courses hiện grid CourseCard đúng
- [ ] Search + filter + sort hoạt động
- [ ] Pagination chuyển trang đúng
- [ ] Course detail hiện đúng 2 cột
- [ ] Video trial mở được trong modal
- [ ] Nút hành động logic đúng (guest/chưa mua/đã mua)
- [ ] Responsive trên mobile

Tick F2.4 → F2.5 trong `QLCV/QLCV-FE.md`.

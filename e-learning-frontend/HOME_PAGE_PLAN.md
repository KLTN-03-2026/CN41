# Kế Hoạch Bổ Sung HomePage — E-Learning

> Tạo: 07/05/2026 | Context: Bổ sung 3 section mới vào HomePage client

---

## Trạng Thái Hiện Tại

HomePage đã có 5 section hoàn chỉnh:

```
HomePage.vue
├── HeroSection.vue          ✅ Split layout, SVG illustration, search bar
├── FeaturedCategories.vue   ✅ Grid 8 danh mục, Lucide icons
├── FeaturedCourses.vue      ✅ Grid 4 cột, rating stars, skeleton
├── LatestPosts.vue          ✅ Grid 3 bài blog
└── CtaSection.vue           ✅ Ẩn khi đã login
```

---

## Mục Tiêu: Thêm 3 Section Mới

```
HomePage.vue (sau khi xong)
├── HeroSection              ✅ done
├── StatsSection             ← MỚI (1) — ngay dưới Hero
├── FeaturedCategories       ✅ done
├── FeaturedCourses          ✅ done
├── WhyUsSection             ← MỚI (2) — sau FeaturedCourses
├── FeaturedTeachers         ← MỚI (3) — sau WhyUs
├── LatestPosts              ✅ done
└── CtaSection               ✅ done
```

---

## Section 1 — `StatsSection.vue`

### Mô tả
Dải số liệu ngang 4 cột, ngay dưới HeroSection. Không cần API — hard-code số liệu demo.

### Thiết kế
- Nền trắng `bg-white`, có `border-b border-gray-100`
- Padding `py-10`
- Responsive: 2×2 trên mobile (`grid-cols-2`), 4 cột trên desktop (`lg:grid-cols-4`)
- Mỗi ô: icon tròn màu + số lớn (`text-3xl font-extrabold`) + label nhỏ

### Dữ liệu (hard-code)
```ts
const STATS = [
  { icon: BookOpen,  value: '500+',    label: 'Khóa học',   color: 'bg-blue-50 text-blue-500' },
  { icon: Users,     value: '10,000+', label: 'Học viên',   color: 'bg-green-50 text-green-500' },
  { icon: Award,     value: '50+',     label: 'Giảng viên', color: 'bg-purple-50 text-purple-500' },
  { icon: Star,      value: '4.8/5',   label: 'Đánh giá',   color: 'bg-yellow-50 text-yellow-500' },
]
```

### Animation
- Dùng `IntersectionObserver` để trigger count-up khi scroll vào viewport
- Số đếm từ 0 lên giá trị cuối trong 1.5s, easing ease-out
- Chỉ animate số thuần (500, 10000, 50), giữ nguyên ký tự (+, /, 4.8)

### Icons (lucide-vue-next — đã có sẵn)
`BookOpen`, `Users`, `Award`, `Star`

---

## Section 2 — `WhyUsSection.vue`

### Mô tả
4 lý do chọn E-Learning. Hoàn toàn static, không cần API.

### Thiết kế
- Nền `bg-gray-50`
- Padding `py-16`
- Header: tiêu đề trái + mô tả phụ
- Grid 2×2 desktop (`grid-cols-2`), 1 cột mobile
- Mỗi ô: icon tròn + tiêu đề bold + mô tả 2 dòng, có hover shadow nhẹ

### Dữ liệu (hard-code)
```ts
const REASONS = [
  {
    icon: Target,
    title: 'Lộ trình học cá nhân hoá',
    desc: 'Hệ thống gợi ý khóa học phù hợp với mục tiêu và trình độ của từng học viên.',
    bg: 'bg-blue-50', color: 'text-blue-500',
  },
  {
    icon: GraduationCap,
    title: 'Giảng viên chuyên nghiệp',
    desc: 'Đội ngũ giảng viên giàu kinh nghiệm thực tế, được kiểm duyệt chặt chẽ.',
    bg: 'bg-green-50', color: 'text-green-500',
  },
  {
    icon: BadgeCheck,
    title: 'Chứng chỉ được công nhận',
    desc: 'Nhận chứng chỉ hoàn thành có giá trị sau mỗi khóa học.',
    bg: 'bg-purple-50', color: 'text-purple-500',
  },
  {
    icon: Zap,
    title: 'Học mọi lúc, mọi nơi',
    desc: 'Truy cập toàn bộ nội dung trên mọi thiết bị, không giới hạn thời gian.',
    bg: 'bg-yellow-50', color: 'text-yellow-500',
  },
]
```

### Icons (lucide-vue-next)
`Target`, `GraduationCap`, `BadgeCheck`, `Zap`

---

## Section 3 — `FeaturedTeachers.vue`

### Mô tả
Grid 4 giảng viên nổi bật. Cần gọi API.

### Backend (không cần thêm gì)
- Route đã có: `GET /api/v1/teachers` (public, không cần auth)
- Response fields: `id`, `name`, `slug`, `image`, `description`, `courses_count`, `status`
- Chỉ trả về active teachers (status=1)
- Gọi với `per_page=4`

### Frontend — file cần sửa

#### `src/services/teacher.service.ts` — THÊM method
```ts
/** GET /teachers?per_page=4 — public, top teachers for homepage */
featured: (limit = 4): Promise<AxiosResponse<PaginatedResponse<Teacher>>> =>
  http.get('/teachers', { params: { per_page: limit } }),
```

#### `src/composables/useHomePage.ts` — THÊM vào Promise.all
```ts
// Thêm import
import { teacherService } from '@/services/teacher.service'
import type { Teacher } from '@/types/course.types'

// Thêm state
const featuredTeachers = ref<Teacher[]>([])

// Thêm vào Promise.all
const [coursesRes, catsRes, postsRes, teachersRes] = await Promise.all([
  courseService.featured(8),
  categoryService.publicList(),
  PostService.getClientPosts({ per_page: 3 }),
  teacherService.featured(4),           // ← THÊM
])
featuredTeachers.value = teachersRes.data.data?.data ?? []   // paginated response

// Thêm vào return
return { featuredCourses, categories, latestPosts, featuredTeachers, loading }
```

> **Lưu ý:** `teacherService.publicList()` trả về `PaginatedResponse` nên data nằm ở `res.data.data.data` (paginated).

#### `FeaturedTeachers.vue` — Thiết kế card

```
┌────────────────────────────┐
│   [Avatar 80px, rounded]   │
│   Nguyễn Văn A             │  ← font-semibold
│   12 khóa học              │  ← text-xs text-gray-400
│   "Chuyên gia Laravel..."  │  ← line-clamp-2 text-sm
│   [Xem hồ sơ →]            │  ← router-link /teachers/{slug}
└────────────────────────────┘
```

- Avatar: `<img>` nếu có `image`, fallback div gradient với chữ cái đầu tên
- Gradient fallback theo index: blue/green/purple/orange
- Hover: `hover:shadow-lg`, card scale nhẹ `group-hover:scale-[1.02]`
- Nền section: `bg-white`
- Grid: `grid-cols-1 sm:grid-cols-2 lg:grid-cols-4`
- Skeleton: 4 card animate-pulse khi loading

---

## Danh Sách File Cần Tạo / Sửa

| # | File | Loại | Việc làm |
|---|------|------|---------|
| 1 | `src/services/teacher.service.ts` | **Sửa** | Thêm method `featured(limit = 4)` |
| 2 | `src/composables/useHomePage.ts` | **Sửa** | Thêm teachers vào Promise.all, export `featuredTeachers` |
| 3 | `src/components/client/home/StatsSection.vue` | **Tạo mới** | Static + count-up animation |
| 4 | `src/components/client/home/WhyUsSection.vue` | **Tạo mới** | Static hoàn toàn |
| 5 | `src/components/client/home/FeaturedTeachers.vue` | **Tạo mới** | Gọi API, avatar fallback |
| 6 | `src/views/client/HomePage.vue` | **Sửa** | Import + lắp 3 section đúng vị trí |

---

## Thứ Tự Implement

```
Bước 1 — teacher.service.ts      → thêm featured()
Bước 2 — useHomePage.ts          → thêm featuredTeachers vào Promise.all
Bước 3 — StatsSection.vue        → tạo (nhanh, static)
Bước 4 — WhyUsSection.vue        → tạo (nhanh, static)
Bước 5 — FeaturedTeachers.vue    → tạo (cần xử lý API + avatar fallback)
Bước 6 — HomePage.vue            → import + lắp đúng thứ tự
```

---

## Lưu Ý Kỹ Thuật

### teacher.service.ts — import type
```ts
import type { Teacher } from '@/types/course.types'
// Teacher type đã có: id, name, slug, image, description, courses_count, status
```

### useHomePage.ts — paginated vs array
- `courseService.featured()` → `ApiResponse<Course[]>` → lấy `res.data.data`
- `categoryService.publicList()` → `ApiResponse<Category[]>` → lấy `res.data.data`
- `PostService.getClientPosts()` → `{ data: { data: Post[] } }` → lấy `res.data.data`
- `teacherService.featured()` → `PaginatedResponse<Teacher>` → lấy `res.data.data` (là array trong paginated)

### StatsSection count-up
```ts
// Chỉ đếm phần số, bỏ ký tự đặc biệt
// '500+' → đếm từ 0→500, hiển thị '500+'
// '4.8/5' → không đếm, hiển thị trực tiếp
// '10,000+' → đếm từ 0→10000, format với dấu phẩy
```

### FeaturedTeachers avatar fallback
```ts
function avatarInitial(name: string): string {
  return name.split(' ').map(w => w[0]).slice(-2).join('').toUpperCase()
}
const AVATAR_GRADIENTS = [
  'from-blue-400 to-blue-600',
  'from-green-400 to-green-600',
  'from-purple-400 to-purple-600',
  'from-orange-400 to-orange-600',
]
```

---

## Kết Quả Mong Đợi

HomePage hoàn chỉnh theo thứ tự:
1. **Hero** — Split layout, search, illustration
2. **Stats** — 500+ khóa học · 10,000+ học viên · 50+ giảng viên · 4.8★
3. **Danh mục** — 8 danh mục, Lucide icons
4. **Khóa học nổi bật** — 8 khóa top rating
5. **Tại sao chọn** — 4 lý do, static
6. **Giảng viên** — 4 giảng viên active
7. **Blog** — 3 bài mới nhất
8. **CTA** — Đăng ký (ẩn khi đã login)

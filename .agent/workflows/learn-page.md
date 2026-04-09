---
description: "FE 2.7 — Client: LearnPage (Video.js + sidebar + progress tracking)"
---

# FE 2.7 — Client LearnPage

> **Task F2.7** — Tạo trang xem bài giảng với video player và sidebar.
> Prerequisite: Backend Module Lessons + Progress API đã có.

---

## Context

```
Stack: Vue 3, Tailwind, @videojs-player/vue + video.js, lucide-vue-next,
       FwbProgress (flowbite-vue), @vueuse/core, progressApi.
Route: /courses/:slug/learn?lesson=:lessonId
```

---

## Bước 1 — Tạo progressApi

`src/api/progressApi.js`:
```js
update(lessonId, data)       → POST /api/v1/lessons/{id}/progress
getCourseProgress(slug)      → GET /api/v1/courses/{slug}/progress
```

---

## Bước 2 — Tạo LearnPage.vue

Tạo `src/pages/client/LearnPage.vue`:

### Layout:
- **Video player** (70% màn hình trái, chiếm hết chiều cao)
- **Sidebar bài giảng** (30% bên phải, có thể toggle ẩn/hiện)

### Video player:
```vue
<VideoPlayer
  :src="currentLesson.video_url"
  controls
  :volume="0.8"
  @timeupdate="onTimeUpdate"
  @ended="onVideoEnded"
/>
```

- `onTimeUpdate` → debounce 5s → gọi `progressApi.update(lessonId, {watched_seconds})`
- `onEnded` → gọi `progressApi.update(lessonId, {is_completed: 1, watched_seconds: duration})`

### Sidebar:
- Danh sách bài giảng nhóm theo section
- Tick check bài đã hoàn thành (`is_completed`)
- Bài đang xem highlight
- `FwbProgress` bar % hoàn thành toàn khóa

### Sidebar toggle:
- `ChevronLeft`/`ChevronRight` icon từ lucide

### Nút điều hướng:
- "Bài trước" / "Bài tiếp" (`ArrowLeft` / `ArrowRight` từ lucide)

---

## ✅ Kiểm tra hoàn thành

- [ ] Video player phát đúng
- [ ] Progress tracking gửi API debounce 5s
- [ ] onEnded đánh dấu hoàn thành
- [ ] Sidebar hiện danh sách bài đúng
- [ ] Chuyển bài trước/tiếp hoạt động
- [ ] Sidebar toggle hoạt động
- [ ] Progress bar % cập nhật

Tick F2.7 trong `QLCV/QLCV-FE.md`.

---
description: "FE 2.6 — Admin: Quản lý Lessons (DataTable + upload video/doc)"
---

# FE 2.6 — Admin Lessons Page

> **Task F2.6** — Tạo trang quản lý bài giảng trong Admin.
> Prerequisite: Backend Module Lessons + Cloudinary upload đã có.

---

## Context

```
Stack: Vue 3, Tailwind, Pinia (adminAuth), Lucide icons, Vue Toastification.
Backend:
  - GET/POST/PUT/DELETE /api/v1/admin/lessons
  - POST /api/v1/admin/upload/video → { video_id, url, name }
  - POST /api/v1/admin/upload/document → { url, name }
```

---

## Bước 1 — Tạo API files

### `src/api/lessonApi.js`:
```js
adminGetList(params), adminCreate(data), adminUpdate(id, data), adminDelete(id), adminReorder(data)
```

### `src/api/uploadApi.js`:
```js
uploadVideo(file, onProgress), uploadDocument(file)
```

> Tham khảo `Promt-set-up/handbook.md` — Section 11 (Upload).

---

## Bước 2 — Tạo LessonsPage.vue

Tạo `src/pages/admin/LessonsPage.vue`:

- **DataTable:** Thứ tự | Tên | Khóa học | Loại (video/doc) | Trial | Published | Thao tác
- **CRUD Modal:**
  - Chọn khóa học (dropdown)
  - Tên bài giảng
  - Mô tả
  - Upload video (progress bar) hoặc document
  - Toggle: is_trial, is_published
  - Thứ tự (order)
- **Reorder:** kéo thả hoặc nút lên/xuống
- **Toggle publish:** bật/tắt trực tiếp trên bảng
- **Search + Filter theo khóa học + Pagination**

---

## Bước 3 — Upload component với progress bar

Tạo upload component (hoặc tích hợp trực tiếp trong modal):

```
<input type="file" accept="video/mp4,video/webm" @change="handleUpload" />
<div v-if="progress > 0">
  <progress bar width: progress%>
  <p>{{ progress }}%</p>
</div>
```

---

## ✅ Kiểm tra hoàn thành

- [ ] DataTable lessons hiện đúng
- [ ] Thêm/Sửa/Xóa lesson hoạt động
- [ ] Upload video + hiện progress bar
- [ ] Upload document
- [ ] Toggle publish/trial
- [ ] Reorder hoạt động
- [ ] Search + Filter + Pagination

Tick F2.6 trong `QLCV/QLCV-FE.md`.

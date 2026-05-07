# Blog / Bài viết (Posts)

## 1. Tổng quan

Module `Posts` cung cấp hệ thống blog cho nền tảng: admin tạo bài viết, học viên đọc và bình luận. Bài viết hỗ trợ danh mục, tags, lượt xem, và bình luận lồng nhau (nested comments).

---

## 2. Cấu trúc dữ liệu

### Quan hệ giữa các bảng

```
post_categories (1) ──── (N) posts ──── (N) post_comments
                              │
                              └── (N:M) tags  (pivot: post_tag)
```

### Bảng `posts`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint | PK |
| `title` | varchar | Tiêu đề bài viết |
| `slug` | varchar unique | URL-friendly |
| `content` | longtext | Nội dung HTML (rich text) |
| `thumbnail` | varchar nullable | Ảnh bìa |
| `author_id` | bigint FK | Tác giả → `users` (admin staff) |
| `post_category_id` | bigint FK | Danh mục bài viết |
| `is_published` | tinyint default 0 | 0 = draft, 1 = published |
| `published_at` | timestamp nullable | Thời điểm publish |
| `views` | int default 0 | Lượt xem |
| `deleted_at` | timestamp | Soft delete |

### Bảng `post_categories`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint | PK |
| `name` | varchar | Tên danh mục |
| `slug` | varchar unique | URL |
| `description` | text nullable | Mô tả |

### Bảng `tags`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint | PK |
| `name` | varchar | Tên tag |
| `slug` | varchar unique | URL |

### Bảng `post_tag` (pivot)

| Cột | Kiểu |
|-----|------|
| `post_id` | bigint FK |
| `tag_id` | bigint FK |

### Bảng `post_comments`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint | PK |
| `post_id` | bigint FK | Bài viết |
| `user_id` | bigint | ID người bình luận |
| `user_type` | varchar | `admin` hoặc `student` |
| `content` | text | Nội dung bình luận |
| `parent_id` | bigint nullable FK | Bình luận cha (nested) |
| `is_approved` | tinyint default 0 | 0 = chờ duyệt, 1 = hiển thị |

`user_id` + `user_type` dùng polymorphic-style để phân biệt commenter là admin hay student — accessor `getCommenterAttribute()` tự động resolve đúng model.

---

## 3. API Endpoints

### Admin

| Method | Endpoint | Permission | Mô tả |
|--------|----------|-----------|-------|
| GET | `/api/v1/admin/post-categories` | posts.view | Danh sách danh mục |
| POST | `/api/v1/admin/post-categories` | posts.create | Tạo danh mục |
| PATCH | `/api/v1/admin/post-categories/{id}` | posts.edit | Cập nhật |
| DELETE | `/api/v1/admin/post-categories/{id}` | posts.delete | Xóa |
| GET | `/api/v1/admin/tags` | tags.view | Danh sách tags |
| POST | `/api/v1/admin/tags` | tags.create | Tạo tag |
| PATCH | `/api/v1/admin/tags/{id}` | tags.edit | Cập nhật |
| DELETE | `/api/v1/admin/tags/{id}` | tags.delete | Xóa |
| GET | `/api/v1/admin/posts` | posts.view | Danh sách bài viết |
| POST | `/api/v1/admin/posts` | posts.create | Tạo bài viết |
| GET | `/api/v1/admin/posts/{id}` | posts.view | Chi tiết |
| PATCH | `/api/v1/admin/posts/{id}` | posts.edit | Cập nhật |
| DELETE | `/api/v1/admin/posts/{id}` | posts.delete | Soft delete |
| PATCH | `/api/v1/admin/posts/{id}/toggle-status` | posts.edit | Bật/tắt publish |
| GET | `/api/v1/admin/posts/trashed` | posts.view | Danh sách đã xóa |
| PATCH | `/api/v1/admin/posts/{id}/restore` | posts.edit | Khôi phục |
| DELETE | `/api/v1/admin/posts/{id}/force-delete` | posts.delete | Xóa vĩnh viễn |
| DELETE | `/api/v1/admin/posts/bulk-delete` | posts.delete | Xóa hàng loạt |
| GET | `/api/v1/admin/post-comments` | comments.view | Danh sách bình luận |
| PATCH | `/api/v1/admin/post-comments/{id}/approve` | comments.edit | Duyệt bình luận |
| DELETE | `/api/v1/admin/post-comments/{id}` | comments.delete | Xóa bình luận |

### Public (không cần auth)

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/v1/posts` | Danh sách bài viết published (filter, search, phân trang) |
| GET | `/api/v1/posts/{slug}` | Chi tiết bài viết + comments đã duyệt |
| POST | `/api/v1/posts/{id}/view` | Tăng lượt xem (+1) |

### Student (auth:api)

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| POST | `/api/v1/posts/{id}/comments` | Đăng bình luận |

---

## 4. Luồng tạo bài viết (Admin)

```
Admin mở PostFormPage.vue
  │
  ├── Nhập: title, slug (auto-generate từ title), content (rich text editor)
  ├── Chọn post_category
  ├── Chọn tags (multi-select)
  ├── Upload thumbnail
  ├── Chọn is_published: draft hoặc published
  │
  │  POST /api/v1/admin/posts
  │  Body: { title, slug, content, post_category_id, tag_ids[], thumbnail, is_published }
  ▼
AdminPostController::store()
  │
  ├── Validate: slug unique, tag_ids exists:tags
  ├── DB::transaction():
  │     ├── Post::create(validated)
  │     ├── [is_published = 1] → set published_at = now()
  │     └── $post->tags()->sync(tag_ids)
  └── Return 201: PostResource
```

---

## 5. Luồng bình luận

```
Học viên đọc bài viết
  │
  │  POST /api/v1/posts/{id}/comments
  │  Body: { "content": "...", "parent_id": null }  ← parent_id để reply
  ▼
  ├── Tạo PostComment:
  │     { post_id, user_id: student.id, user_type: 'student', content, parent_id, is_approved: 0 }
  │
  └── Return 201 (bình luận chờ duyệt, chưa hiển thị)

Admin duyệt:
  PATCH /api/v1/admin/post-comments/{id}/approve
  → is_approved = 1 → hiển thị trên trang
```

**Nested comments:** `parent_id` cho phép reply vào bình luận cụ thể. Frontend đệ quy render replies.

---

## 6. Ví dụ response

### GET `/api/v1/posts` — Danh sách bài viết

```json
{
  "success": true,
  "data": [
    {
      "id": 3,
      "title": "Top 5 framework PHP năm 2026",
      "slug": "top-5-framework-php-2026",
      "thumbnail": "http://localhost:8000/storage/posts/php-fw.jpg",
      "published_at": "2026-05-01T08:00:00Z",
      "views": 1240,
      "category": { "id": 2, "name": "Lập trình" },
      "tags": [{ "id": 1, "name": "PHP" }, { "id": 3, "name": "Laravel" }],
      "author": { "name": "Admin" }
    }
  ],
  "pagination": { "current_page": 1, "total": 15, "per_page": 10 }
}
```

### GET `/api/v1/posts/{slug}` — Chi tiết + comments

```json
{
  "success": true,
  "data": {
    "id": 3,
    "title": "Top 5 framework PHP năm 2026",
    "content": "<p>...</p>",
    "views": 1241,
    "comments": [
      {
        "id": 10,
        "content": "Bài viết hay quá!",
        "commenter": { "name": "Nguyễn Văn A" },
        "created_at": "2026-05-03T14:00:00Z",
        "replies": [
          {
            "id": 11,
            "content": "Cảm ơn bạn!",
            "commenter": { "name": "Admin" }
          }
        ]
      }
    ]
  }
}
```

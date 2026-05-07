# API Reference — Posts & Blog

Base URL: `http://localhost:8000/api/v1`

---

## Admin — Post Categories

### GET `/admin/post-categories`

**Middleware:** `auth:admin`, `permission:posts.view`

---

### POST `/admin/post-categories`

```json
{ "name": "Lập trình", "slug": "lap-trinh", "description": "..." }
```

---

### PATCH `/admin/post-categories/{id}`

---

### DELETE `/admin/post-categories/{id}`

---

## Admin — Tags

### GET `/admin/tags`

**Middleware:** `auth:admin`, `permission:tags.view`

---

### POST `/admin/tags`

```json
{ "name": "Laravel", "slug": "laravel" }
```

---

### PATCH `/admin/tags/{id}`

---

### DELETE `/admin/tags/{id}`

---

## Admin — Posts

### GET `/admin/posts`

**Middleware:** `auth:admin`, `permission:posts.view`

**Query params:** `search`, `is_published`, `post_category_id`, `page`, `per_page`

---

### POST `/admin/posts`

**Request Body:**
```json
{
  "title": "Top 5 framework PHP năm 2026",
  "slug": "top-5-framework-php-2026",
  "content": "<p>Nội dung HTML...</p>",
  "thumbnail": "posts/thumbnail.jpg",
  "post_category_id": 2,
  "tag_ids": [1, 3, 5],
  "is_published": 1
}
```

**Validation:**
| Field | Rules |
|-------|-------|
| `title` | required, string, max:255 |
| `slug` | required, unique:posts, regex slug |
| `content` | required, string |
| `post_category_id` | required, exists:post_categories,id |
| `tag_ids.*` | integer, exists:tags,id |
| `is_published` | in:0,1 |

---

### GET `/admin/posts/{id}`

Chi tiết + tags + category + comment count.

---

### PATCH `/admin/posts/{id}`

---

### DELETE `/admin/posts/{id}`

Soft delete.

---

### PATCH `/admin/posts/{id}/toggle-publish`

Bật/tắt `is_published`. Tự động set/clear `published_at`.

---

### POST `/admin/posts/bulk-delete`

```json
{ "ids": [1, 2, 3] }
```

---

## Admin — Comments

### GET `/admin/comments`

**Query params:** `post_id`, `is_approved`, `page`, `per_page`

---

### PATCH `/admin/comments/{id}/toggle-approval`

Duyệt hoặc từ chối bình luận.

**Response 200:**
```json
{ "data": { "id": 10, "is_approved": 1 } }
```

---

### DELETE `/admin/comments/{id}`

---

### POST `/admin/comments/bulk-delete`

```json
{ "ids": [10, 11, 12] }
```

---

## Public

### GET `/posts`

**Query params:** `search`, `post_category_id`, `tag_id`, `page`, `per_page`

Chỉ trả bài viết có `is_published = 1`.

**Response 200:**
```json
{
  "data": [
    {
      "id": 3,
      "title": "Top 5 framework PHP năm 2026",
      "slug": "top-5-framework-php-2026",
      "thumbnail": "http://...",
      "published_at": "2026-05-01T08:00:00Z",
      "views": 1240,
      "category": { "id": 2, "name": "Lập trình" },
      "tags": [{ "id": 1, "name": "PHP" }],
      "author": { "name": "Admin" }
    }
  ],
  "pagination": { ... }
}
```

---

### GET `/posts/{slug}`

Chi tiết + comments đã duyệt (`is_approved = 1`), lồng nhau theo `parent_id`.

---

### POST `/posts/{id}/increment-views`

Tăng `views + 1`. Không cần auth, gọi mỗi lần user mở bài viết.

**Response 200:** `{ "data": { "views": 1241 } }`

---

### GET `/post-categories`

Danh sách danh mục bài viết public.

---

### GET `/tags`

Danh sách tags public.

---

## Student (auth:api)

### POST `/posts/{id}/comments`

Đăng bình luận.

**Request Body:**
```json
{
  "content": "Bài viết rất hay!",
  "parent_id": null
}
```

`parent_id`: ID của bình luận cha nếu là reply, `null` nếu là comment gốc.

**Response 201:**
```json
{
  "data": {
    "id": 15,
    "content": "Bài viết rất hay!",
    "is_approved": 0,
    "created_at": "2026-05-07T15:00:00Z"
  }
}
```

> Bình luận tạo với `is_approved = 0`, chờ admin duyệt trước khi hiển thị.

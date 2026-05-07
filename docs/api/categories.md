# API Reference — Categories

Base URL: `http://localhost:8000/api/v1`

---

## Admin

### GET `/admin/categories`

**Middleware:** `auth:admin`, `permission:categories.view`

**Query params:** `search`, `status`, `page`, `per_page`

**Response 200:** Danh sách phẳng + phân trang.

---

### GET `/admin/categories/tree`

Trả về cây phân cấp lồng nhau (dùng để hiển thị trang quản lý).

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1, "name": "Lập trình", "slug": "lap-trinh", "depth": 0,
      "children": [
        {
          "id": 2, "name": "Web", "slug": "web", "depth": 1,
          "children": [
            { "id": 3, "name": "Frontend", "depth": 2, "children": [] }
          ]
        }
      ]
    }
  ]
}
```

---

### GET `/admin/categories/flat-tree`

Danh sách phẳng có `depth` — dùng để render dropdown chọn parent với indent.

**Response 200:**
```json
{
  "data": [
    { "id": 1, "name": "Lập trình", "depth": 0 },
    { "id": 2, "name": "Web", "depth": 1 },
    { "id": 3, "name": "Frontend", "depth": 2 }
  ]
}
```

---

### POST `/admin/categories`

**Middleware:** `auth:admin`, `permission:categories.create`

**Request Body:**
```json
{
  "name": "Frontend",
  "slug": "frontend",
  "parent_id": 2,
  "description": "HTML, CSS, JavaScript...",
  "icon": "icon-frontend",
  "status": 1
}
```

**Validation:**
| Field | Rules |
|-------|-------|
| `name` | required, string, max:255 |
| `slug` | required, unique:categories, regex slug |
| `parent_id` | nullable, exists:categories,id |
| `status` | nullable, in:0,1 |

---

### PATCH `/admin/categories/{id}`

Tương tự POST, fields `sometimes`.

---

### DELETE `/admin/categories/{id}`

Soft delete. **Lưu ý:** con cháu không tự động bị xóa — cần xóa thủ công hoặc move trước.

---

### POST `/admin/categories/{id}/move`

Di chuyển node sang vị trí mới trong cây.

**Request Body:**
```json
{ "parent_id": 5 }
```

`parent_id: null` → làm root node. NestedSet tự rebuild `_lft`, `_rgt`, `depth`.

---

### PATCH `/admin/categories/{id}/toggle-status`

---

### GET `/admin/categories/{id}/ancestors`

Danh sách tổ tiên từ root đến cha trực tiếp.

**Response 200:**
```json
{
  "data": [
    { "id": 1, "name": "Lập trình", "depth": 0 },
    { "id": 2, "name": "Web", "depth": 1 }
  ]
}
```

---

### GET `/admin/categories/{id}/descendants`

Tất cả con cháu của node.

---

### GET `/admin/categories/trashed`

---

### PATCH `/admin/categories/{id}/restore`

---

### DELETE `/admin/categories/{id}/force-delete`

---

### DELETE `/admin/categories/bulk-delete`

```json
{ "ids": [3, 4, 5] }
```

---

### PATCH `/admin/categories/bulk-restore`

```json
{ "ids": [3, 4] }
```

---

## Public

### GET `/categories`

Danh sách categories active có ít nhất 1 khóa học published.

**Query params:** `page`, `per_page`

---

### GET `/categories/tree`

Cây danh mục public kèm `course_count`.

---

### GET `/categories/{slug}`

Chi tiết category + số khóa học.

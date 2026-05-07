# Danh mục khóa học (Categories)

## 1. Tổng quan

Module `Categories` quản lý danh mục phân cấp nhiều cấp cho khóa học. Dùng thuật toán **NestedSet** (package `kalnoy/nestedset`) để lưu cây phân cấp trong database, cho phép truy vấn ancestors/descendants hiệu quả mà không cần JOIN đệ quy.

---

## 2. NestedSet là gì?

Thay vì chỉ lưu `parent_id` (Adjacency List), NestedSet lưu thêm 3 cột:

| Cột | Mô tả |
|-----|-------|
| `_lft` | Giá trị left của node |
| `_rgt` | Giá trị right của node |
| `depth` | Độ sâu trong cây (root = 0) |

**Ví dụ cây 3 cấp:**

```
Lập trình (_lft=1, _rgt=10)
  ├── Web (_lft=2, _rgt=7)
  │     ├── Frontend (_lft=3, _rgt=4)
  │     └── Backend  (_lft=5, _rgt=6)
  └── Mobile (_lft=8, _rgt=9)
```

Truy vấn tất cả con cháu của "Lập trình":
```sql
WHERE _lft > 1 AND _rgt < 10
-- → Chỉ 1 query, không cần đệ quy
```

---

## 3. Cấu trúc dữ liệu

### Bảng `categories`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint | PK |
| `name` | varchar | Tên danh mục |
| `slug` | varchar unique | URL-friendly |
| `description` | text nullable | Mô tả |
| `icon` | varchar nullable | Icon (class hoặc URL) |
| `status` | tinyint default 1 | 1 = active, 0 = inactive |
| `order` | int default 0 | Thứ tự hiển thị trong cùng cấp |
| `parent_id` | bigint nullable FK | Cha của node (null = root) |
| `_lft` | int unsigned | NestedSet left value |
| `_rgt` | int unsigned | NestedSet right value |
| `depth` | int unsigned | Độ sâu (0 = root) |
| `deleted_at` | timestamp | Soft delete |

---

## 4. API Endpoints

### Admin

| Method | Endpoint | Permission | Mô tả |
|--------|----------|-----------|-------|
| GET | `/api/v1/admin/categories` | categories.view | Danh sách phẳng (flat, phân trang) |
| GET | `/api/v1/admin/categories/tree` | categories.view | Dạng cây lồng nhau |
| GET | `/api/v1/admin/categories/flat-tree` | categories.view | Phẳng nhưng có indent (dùng cho select) |
| POST | `/api/v1/admin/categories` | categories.create | Tạo danh mục |
| GET | `/api/v1/admin/categories/{id}` | categories.view | Chi tiết |
| PATCH | `/api/v1/admin/categories/{id}` | categories.edit | Cập nhật |
| DELETE | `/api/v1/admin/categories/{id}` | categories.delete | Soft delete |
| PATCH | `/api/v1/admin/categories/{id}/move` | categories.edit | Di chuyển vị trí trong cây |
| PATCH | `/api/v1/admin/categories/{id}/toggle-status` | categories.edit | Bật/tắt |
| GET | `/api/v1/admin/categories/{id}/ancestors` | categories.view | Danh sách tổ tiên |
| GET | `/api/v1/admin/categories/{id}/descendants` | categories.view | Danh sách con cháu |
| GET | `/api/v1/admin/categories/trashed` | categories.view | Đã xóa |
| PATCH | `/api/v1/admin/categories/{id}/restore` | categories.edit | Khôi phục |
| DELETE | `/api/v1/admin/categories/{id}/force-delete` | categories.delete | Xóa vĩnh viễn |
| DELETE | `/api/v1/admin/categories/bulk-delete` | categories.delete | Xóa hàng loạt |
| PATCH | `/api/v1/admin/categories/bulk-restore` | categories.edit | Khôi phục hàng loạt |

### Public (không cần auth)

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/v1/categories` | Danh sách category có khóa học published |
| GET | `/api/v1/categories/tree` | Cây danh mục public |
| GET | `/api/v1/categories/{slug}` | Chi tiết + số khóa học |

---

## 5. Các truy vấn NestedSet thường dùng

```php
// Lấy toàn bộ cây với depth
Category::withDepth()->get();

// Lấy dạng cây lồng nhau
Category::get()->toTree();

// Tổ tiên của một node (từ root đến cha)
$category->ancestors()->get();
// VD: Frontend → [Lập trình, Web]

// Tất cả con cháu
$category->descendants()->get();
// VD: Lập trình → [Web, Frontend, Backend, Mobile]

// Con trực tiếp
$category->children()->get();

// Chỉ root nodes
Category::whereIsRoot()->get();

// Di chuyển node sang cha mới
$category->appendToNode($newParent)->save();
// Hoặc làm root:
$category->makeRoot()->save();
```

---

## 6. Luồng Frontend — Category Tree

Frontend dùng composable `useCategoryTree.ts` để build cây từ flat array:

```
GET /api/v1/admin/categories/flat-tree
  → [
      { id: 1, name: "Lập trình", depth: 0, _lft: 1 },
      { id: 2, name: "Web",       depth: 1, _lft: 2 },
      { id: 3, name: "Frontend",  depth: 2, _lft: 3 },
      ...
    ]
```

`flat-tree` dùng để render dropdown chọn danh mục cha trong form tạo/sửa, với indent theo `depth`:

```
Lập trình
  ── Web
     ──── Frontend
     ──── Backend
  ── Mobile
```

Component `CategoryTreeNode.vue` render đệ quy cây phân cấp trong trang quản lý.

---

## 7. Di chuyển node trong cây

```
PATCH /api/v1/admin/categories/{id}/move
Body: { "parent_id": 5 }   ← null để làm root
  │
  ▼
CategoriesController::move()
  │
  ├── [parent_id != null]
  │     ├── Tìm parent category
  │     ├── Kiểm tra không tạo vòng lặp (parent không phải con cháu của node)
  │     └── $category->appendToNode($parent)->save()
  │           → NestedSet tự tính lại _lft, _rgt cho toàn bộ cây
  │
  └── [parent_id == null]
        └── $category->makeRoot()->save()
```

NestedSet tự động rebuild `_lft`, `_rgt`, `depth` cho toàn bộ các node bị ảnh hưởng sau mỗi lần di chuyển.

---

## 8. Gắn danh mục vào khóa học

Quan hệ N:M qua bảng `categories_courses`:

```php
// Gắn danh mục khi tạo/sửa khóa học
$course->categories()->sync($categoryIds);
// → xóa các danh mục cũ, thêm danh mục mới
```

Một khóa học có thể thuộc nhiều danh mục. Frontend dùng multi-select với danh sách từ `flat-tree`.

---

## 9. Ví dụ response

### GET `/api/v1/categories/tree` — Cây public

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Lập trình",
      "slug": "lap-trinh",
      "course_count": 15,
      "children": [
        {
          "id": 2,
          "name": "Web",
          "slug": "web",
          "course_count": 8,
          "children": [
            { "id": 3, "name": "Frontend", "slug": "frontend", "course_count": 4, "children": [] },
            { "id": 4, "name": "Backend",  "slug": "backend",  "course_count": 4, "children": [] }
          ]
        }
      ]
    }
  ]
}
```

### GET `/api/v1/admin/categories/{id}/ancestors`

```json
{
  "success": true,
  "data": [
    { "id": 1, "name": "Lập trình", "depth": 0 },
    { "id": 2, "name": "Web", "depth": 1 }
  ]
}
```

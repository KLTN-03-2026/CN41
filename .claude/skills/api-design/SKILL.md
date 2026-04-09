# API Design Skill

Design new API endpoints following this project's established patterns.

## Design Process

1. Identify the resource and which guard it belongs to (`admin`, `api`, or public)
2. Choose the module — every endpoint lives inside a Nwidart module
3. Plan routes following naming conventions
4. Design request/response shape
5. Identify repository methods needed

## Route Design Rules

```
GET    /api/v1/admin/{resources}                  List (paginated)
POST   /api/v1/admin/{resources}                  Create
GET    /api/v1/admin/{resources}/{id}             Show
PATCH  /api/v1/admin/{resources}/{id}             Update (partial)
DELETE /api/v1/admin/{resources}/{id}             Soft delete
PATCH  /api/v1/admin/{resources}/{id}/toggle-status
GET    /api/v1/admin/{resources}/trashed          Trash bin
PATCH  /api/v1/admin/{resources}/{id}/restore
DELETE /api/v1/admin/{resources}/{id}/force-delete
DELETE /api/v1/admin/{resources}/bulk-delete      Body: { ids: [1,2,3] }

GET    /api/v1/{resources}                        Public list
GET    /api/v1/{resources}/{slug}                 Public show (by slug)

GET    /api/v1/my-{resources}                     Authenticated student's own
POST   /api/v1/{resources}/{slug}/enroll-free
```

**Use PATCH not PUT** — the project never replaces entire resources.
**Use slugs for public-facing routes**, IDs for admin routes.

## Response Design

Always use the `ApiResponse` envelope:

```json
// List response
{
  "success": true,
  "message": "Lấy danh sách thành công.",
  "data": [ { "id": 1, "name": "...", ... } ],
  "pagination": { "current_page": 1, "last_page": 3, "per_page": 15, "total": 45 }
}

// Single resource
{
  "success": true,
  "message": "Lấy thông tin thành công.",
  "data": { "id": 1, "name": "...", "teacher": { ... }, "categories": [ ... ] }
}

// Created
{ "success": true, "message": "Tạo thành công.", "data": { ... } }   // HTTP 201

// Validation error
{ "success": false, "message": "Dữ liệu không hợp lệ.", "errors": { "field": ["msg"] } }  // HTTP 422
```

## Middleware Selection

| Situation | Middleware stack |
|-----------|-----------------|
| Admin CRUD | `auth:admin` |
| Student reads (no verification needed) | `auth:api` |
| Student actions (enroll, purchase) | `auth:api`, `email.verified` |
| Public (course listing, course detail) | none |
| Login/register endpoints | `throttle:5,1` or `throttle:10,1` |

## Request Validation Template

```php
// StoreMyResourceRequest.php
public function rules(): array
{
    return [
        'name'    => 'required|string|max:255',
        'status'  => 'sometimes|boolean',
        'ids'     => 'required|array',         // for bulk operations
        'ids.*'   => 'integer|exists:table,id',
    ];
}
```

## Repository Interface Extension

When designing a new resource, specify:

```php
interface MyResourceRepositoryInterface extends RepositoryInterface
{
    // Inherited base: getAll, find, findOrFail, create, update, delete,
    //                 paginate, paginateTrashed, restore, forceDeleteById, actionMany

    // Add only what's unique to this resource:
    public function getFiltered(array $filters, int $perPage): LengthAwarePaginator;
    public function findBySlug(string $slug): ?MyModel;
    public function toggleStatus(int $id): MyModel;
}
```

## Resource Transformer Checklist

Include in the Resource:
- All safe scalar fields (id, name, slug, status, timestamps)
- Computed/formatted fields (thumbnail → full URL via `asset('storage/' . $this->thumbnail)`)
- Relationships with `$this->whenLoaded('relation')` — never eager-load silently
- Exclude: passwords, internal tokens, raw file paths

## Full Example — Designing a "Reviews" Endpoint

**Requirement**: Students can post reviews on completed courses.

**Routes**:
```
POST   /api/v1/courses/{slug}/reviews        [auth:api, email.verified]
GET    /api/v1/courses/{slug}/reviews        public
PATCH  /api/v1/reviews/{id}                 [auth:api] (own review only)
DELETE /api/v1/admin/reviews/{id}           [auth:admin]
GET    /api/v1/admin/reviews                [auth:admin]
```

**Module**: `Modules/Reviews/`

**Table**: `reviews` (id, course_id, student_id, rating tinyint, body text, timestamps)

**Repository interface adds**:
```php
public function getByCourse(int $courseId, int $perPage): LengthAwarePaginator;
public function getByStudent(int $studentId, int $courseId): ?Review;
public function getAverageRating(int $courseId): float;
```

**Validation** (`StoreReviewRequest`):
```php
'rating' => 'required|integer|min:1|max:5',
'body'   => 'required|string|min:10|max:1000',
```

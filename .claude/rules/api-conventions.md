# API Conventions

## Versioning & Base URL
All routes are prefixed with `/api/v1` — set via each module's `RouteServiceProvider`.
Frontend reads base URL from `VITE_API_URL=/api/v1`.

## Route Naming Patterns

```
/api/v1/admin/{resource}           → admin CRUD
/api/v1/{resource}                 → public or student-facing
/api/v1/my-{resource}              → authenticated student's own data
/api/v1/admin/{resource}/{id}/toggle-status
/api/v1/admin/{resource}/trashed
/api/v1/admin/{resource}/bulk-delete
/api/v1/admin/{resource}/{id}/restore
```

## Standard Response Format

All responses use the `ApiResponse` trait. Never return raw model data.

```json
// Success (single item)
{ "success": true, "message": "...", "data": { ... } }

// Success (paginated list)
{
  "success": true,
  "message": "...",
  "data": [ ... ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75,
    "from": 1,
    "to": 15
  }
}

// Error (validation)
{ "success": false, "message": "Dữ liệu không hợp lệ.", "errors": { "field": ["msg"] } }

// Error (auth / forbidden)
{ "success": false, "message": "...", "data": null, "errors": { ... } }
```

HTTP status codes: 200 OK, 201 Created, 422 Unprocessable, 403 Forbidden, 401 Unauthorized.

## Authentication Guards

Two separate Sanctum guards — never interchange tokens:

| Guard | Token storage key | Middleware | Used for |
|-------|-------------------|-----------|---------|
| `admin` | `adminToken` (localStorage) | `auth:admin` | Staff/admins |
| `api` | `studentToken` (localStorage) | `auth:api` | Students |

Student routes that require email verification also use `email.verified` middleware.

## Throttle Rules

```php
throttle:5,1    // login, logout (5 attempts per minute)
throttle:10,1   // register (10 per minute)
```

## Route Registration Pattern

Each module declares routes in `routes/api.php`, loaded by the module's `RouteServiceProvider`:

```php
// Admin routes (prefix: api/v1/admin, middleware: auth:admin)
Route::prefix('api/v1/admin')->middleware(['auth:admin'])->group(function () {
    Route::get('courses', [AdminCourseController::class, 'index']);
    Route::post('courses', [AdminCourseController::class, 'store']);
    Route::patch('courses/{id}', [AdminCourseController::class, 'update']);
    Route::delete('courses/{id}', [AdminCourseController::class, 'destroy']);
    Route::patch('courses/{id}/toggle-status', [AdminCourseController::class, 'toggleStatus']);
    Route::get('courses/trashed', [AdminCourseController::class, 'trashed']);
    Route::delete('courses/bulk-delete', [AdminCourseController::class, 'bulkDelete']);
});

// Public routes (prefix: api/v1, no auth)
Route::prefix('api/v1')->group(function () {
    Route::get('courses', [CourseController::class, 'index']);
    Route::get('courses/{slug}', [CourseController::class, 'show']);
});

// Student-auth routes (prefix: api/v1, middleware: auth:api, email.verified)
Route::prefix('api/v1')->middleware(['auth:api', 'email.verified'])->group(function () {
    Route::get('my-courses', [CourseController::class, 'myCourses']);
});
```

## Controller Method Signatures

```php
// Always return JsonResponse; accept Request or typed FormRequest
public function index(Request $request): JsonResponse
public function store(StoreCourseRequest $request): JsonResponse
public function show(int $id): JsonResponse
public function update(UpdateCourseRequest $request, int $id): JsonResponse
public function destroy(int $id): JsonResponse
public function toggleStatus(int $id): JsonResponse
public function trashed(Request $request): JsonResponse
public function bulkDelete(Request $request): JsonResponse
```

## Pagination

Query param: `?page=1&per_page=15`
`per_page` is clamped server-side to `[1, 100]` in every repository.

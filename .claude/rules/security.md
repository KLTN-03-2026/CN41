# Security

## Authentication Guards

Two separate Sanctum guards — defined in `config/auth.php`:

| Guard | Model | Token name | Used by |
|-------|-------|-----------|---------|
| `api` | `Modules\Students\Models\Student` | `student-token` | Students |
| `admin` | `Modules\Users\Models\User` | `admin-token` | Admin staff |

**Never mix guards.** An admin token on a student route (and vice versa) returns 401.

Token lifetime: **no expiration by default** (`expiration: null` in `config/sanctum.php`).
Logout revokes only the current token — multiple sessions are allowed.

CORS: only `http://localhost:5173` is whitelisted (`config/cors.php`). Update for production.

## Authorization (Spatie RBAC)

**Guard:** `admin` (all roles/permissions belong to the admin guard only).

**Roles and permissions** (defined in `RolePermissionSeeder`):

| Role | Permissions |
|------|------------|
| `super-admin` | All permissions |
| `admin` | All except `users.delete` |
| `teacher` | `courses.*`, `lessons.*`, `dashboard.view` |

**Permission list:**
```
users.view / create / edit / delete
courses.view / create / edit / delete
categories.view / create / edit / delete
lessons.view / create / edit / delete
orders.view / edit
students.view / edit
dashboard.view
```

Spatie is configured with `display_permission_in_exception: false` and `display_role_in_exception: false` — roles/permission names are never leaked in error messages.

## Middleware

| Alias | Class | Applied to |
|-------|-------|-----------|
| `auth:admin` | Sanctum guard | All admin routes |
| `auth:api` | Sanctum guard | Authenticated student routes |
| `email.verified` | `Modules\Auth\Http\Middleware\EnsureEmailVerified` | Student action routes (enroll, progress, etc.) |
| `throttle:5,1` | Laravel built-in | Admin login, student login |
| `throttle:10,1` | Laravel built-in | Student register |
| `throttle:3,1` | Laravel built-in | Forgot password, resend verification |

`EnsureEmailVerified` checks `auth('api')->user()->email_verified_at`. Returns 403 with `errors.email_not_verified = true` (not a redirect).

Global exception handler (`bootstrap/app.php`) converts all Laravel exceptions to JSON — no HTML error pages leak to API consumers:
- `ModelNotFoundException` → 404
- `AuthenticationException` → 401
- `AccessDeniedHttpException` → 403
- `ValidationException` → 422

## Fields Never Exposed in API Responses

Resources (`*Resource.php`) must exclude:
- `password` / `remember_token`
- `email_verified_at` (internal state)
- Raw file paths — always converted to full URLs via `asset('storage/' . $this->thumbnail)`
- Pivot internals (`laravel_through_key`, etc.)

Use `$this->whenLoaded('relation')` for relationships — never eager-load silently in resources.

## Validation Security Rules

Key rules used across Form Requests:

```php
// Existence checks (prevent orphaned FK inserts)
'teacher_id'     => 'exists:teachers,id'
'category_ids.*' => 'exists:categories,id'
'video_id'       => 'exists:media_files,id'

// Uniqueness (prevent duplication)
'email'          => 'unique:students,email'
'slug'           => 'unique:courses,slug'

// Slug format (prevent XSS / path traversal via slug)
'slug'           => 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'

// Enum guards (prevent unexpected values)
'level'          => 'in:beginner,intermediate,advanced'
'type'           => 'in:video,document,text'
'status'         => 'in:0,1'

// Price sanity
'sale_price'     => 'lte:price'

// Date sanity
'date_of_birth'  => 'before:today'

// Password
'password'       => 'min:8|max:100|confirmed'
```

All Form Requests override `failedValidation()` to return structured JSON (no redirect).
Vietnamese messages in `messages()` — but rules logic is unchanged.

## File Upload Security

Handled in `Modules/Upload/` — all upload routes require `auth:admin`.

| Type | Allowed MIME types | Max size |
|------|--------------------|---------|
| Video (local) | `video/mp4, video/webm, video/quicktime, video/x-matroska` | 500 MB |
| Document | `pdf, doc, docx, txt` | 20 MB |
| Presigned (S3) | `video, document, image` (enum) | 5 GB |

File stream endpoint `/api/v1/media/{id}/stream` accepts auth via Bearer header **or** `?token=` query param (needed because `<video src>` cannot send headers). Token expiry is validated manually.

## Database Security

- All queries go through Eloquent or the repository pattern — no raw SQL in controllers
- `DB::transaction()` wraps multi-step writes — partial state is never persisted
- Soft deletes prevent accidental permanent loss (`deleted_at` pattern)
- `per_page` clamped to `[1, 100]` in every repository — prevents unbounded queries
- `BCRYPT_ROUNDS=12` configured in `.env` for password hashing

## Email Verification Token

```php
// Generation (Student AuthController)
$token = bin2hex(random_bytes(32));   // cryptographically secure
$expiresAt = now()->addHours(24);     // 24-hour window

// Verification
// 1. Lookup token in student_email_verifications
// 2. Check expires_at > now()
// 3. Delete token after use (one-time)
// 4. Set email_verified_at = now()
```

Forgot-password returns HTTP 200 for both valid and invalid emails — prevents user enumeration.
Password reset uses Laravel's broker (`Password::broker('students')`) with 60-minute token expiry and 60-second resend throttle.

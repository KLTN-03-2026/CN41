# Testing

## Current State

```
e-learning-backend/tests/
├── Feature/
│   ├── Admin/
│   │   ├── CategoryTest.php        (11 test cases — CRUD, toggle, pagination, search)
│   │   ├── CourseTest.php
│   │   └── SectionLessonTest.php
│   └── Auth/
│       ├── AdminLoginTest.php
│       └── StudentRegisterTest.php
├── Unit/
│   └── ExampleTest.php             (placeholder)
└── TestCase.php
```

## Running Tests

```bash
cd e-learning-backend
php artisan test              # run all
php artisan test --filter=CategoryTest
php artisan test tests/Feature/Admin/CategoryTest.php
```

## Test Database
Configure `phpunit.xml` to use SQLite in-memory — never the dev database:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

## Auth Setup Pattern

Tests use `User::forceCreate` (bypasses mass-assignment guard) + `actingAs` — not factories,
to avoid dependency on factory state or seeders:

```php
protected function setupAdmin(): User
{
    $admin = User::forceCreate([
        'name'     => 'Admin Test',
        'email'    => 'admin_test@test.com',
        'password' => bcrypt('password'),
    ]);
    $this->actingAs($admin, 'admin');
    return $admin;
}
```

For student routes use `actingAs($student, 'api')` — **never mix guards**.

## Assert Patterns

```php
// Success — check envelope
$response->assertStatus(201)->assertJsonPath('success', true);
$response->assertStatus(200)->assertJsonPath('success', true);

// Validation failure
$response->assertStatus(422)->assertJsonValidationErrors(['slug']);
$response->assertStatus(422)->assertJsonValidationErrors(['name']);

// Database assertions
$this->assertDatabaseHas('categories', ['slug' => 'ky-nang-mem']);
$this->assertSoftDeleted('categories', ['id' => $category->id]);
$this->assertDatabaseHas('categories', ['id' => $id, 'deleted_at' => null]);

// Paginated response
$response->assertJsonPath('pagination.per_page', 10);
$response->assertJsonPath('pagination.total', 20);

// Content presence/absence
$response->assertJsonFragment(['name' => 'Lập trình PHP']);
$response->assertJsonMissing(['name' => 'Thiết kế Web']);
```

## HTTP Method Convention

| Action | Method | Test helper |
|--------|--------|-------------|
| Create | POST | `postJson` |
| Update (partial) | PATCH | `patchJson` |
| Delete (soft) | DELETE | `deleteJson` |
| Toggle status | PATCH | `patchJson` |
| Restore | PATCH | `patchJson` |
| Force delete | DELETE | `deleteJson` |

**Use `patchJson` for updates — not `putJson`.** The project convention is PATCH (never PUT).

## Guidelines When Writing Tests

### Feature Tests (HTTP layer)
- Test against actual routes, not controllers directly
- Use `setupAdmin()` / `actingAs($student, 'api')` for auth
- Create test data directly via `Model::create([...])` — no factories needed for simple cases
- Assert response structure matches the `ApiResponse` envelope

### Unit Tests (Repository / Logic)
- Test repository methods with real DB (SQLite in-memory), not mocks
- Create data, call repository, assert result

### Naming Convention
```
tests/Feature/Admin/CategoryTest.php
tests/Feature/Auth/AdminLoginTest.php
tests/Unit/Course/CourseRepositoryTest.php
```

### What to Test
- Auth: login with wrong credentials, unverified email, rate limiting
- CRUD: create/update/delete + validation errors (422) + duplicate slug
- Status: toggle, soft delete + restore + force delete
- Guard isolation: admin token rejected on student routes and vice versa
- Pagination: `per_page` param, `pagination.*` keys in response

## Manual Testing (API)
Use the test accounts seeded by `php artisan migrate:fresh --seed`:

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@elearning.com | password |
| Admin | admin@elearning.com | password |
| Student | student@elearning.com | password |

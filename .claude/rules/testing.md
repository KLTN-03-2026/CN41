# Testing

## Current State
The project has test scaffolding but **no written tests yet**:
```
e-learning-backend/tests/
├── Feature/      (empty)
├── Unit/         (empty)
└── TestCase.php  (base class only)
```

## Running Tests

```bash
cd e-learning-backend
php artisan test              # run all
php artisan test --filter=CourseTest
php artisan test tests/Feature/CourseTest.php
```

## Test Database
Use a separate test database or SQLite in-memory. Configure in `phpunit.xml`:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

## Guidelines When Writing Tests

### Feature Tests (HTTP layer)
- Test against actual routes, not controllers directly
- Use `actingAs($admin, 'admin')` / `actingAs($student, 'api')` for auth
- Use model factories for test data
- Assert response structure matches the `ApiResponse` envelope:

```php
$response->assertJsonStructure([
    'success', 'message', 'data' => ['id', 'name', 'slug'],
]);
$response->assertJson(['success' => true]);
```

### Unit Tests (Repository / Logic)
- Test repository methods with real DB (SQLite in-memory), not mocks
- Factory-create data, call repository, assert result

### Naming Convention
```
tests/Feature/Course/StoreCourseTest.php
tests/Feature/Auth/StudentLoginTest.php
tests/Unit/Course/CourseRepositoryTest.php
```

### What to Test
- Auth: login with wrong credentials, unverified email, rate limiting
- Courses: CRUD, slug uniqueness, status toggle, soft delete + restore
- Validation: missing required fields return 422 with `errors` key
- Guard isolation: admin token rejected on student routes and vice versa

## Manual Testing (API)
Use the test accounts seeded by `php artisan migrate:fresh --seed`:

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@elearning.com | password |
| Admin | admin@elearning.com | password |
| Student | student@elearning.com | password |

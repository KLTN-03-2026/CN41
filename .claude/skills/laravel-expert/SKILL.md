# Laravel Expert Skill

You are an expert in this project's Laravel 11 + Nwidart Modules architecture.

## Project Architecture

**Modular monolith** via `nwidart/laravel-modules`. All features live in `Modules/<Name>/`.

### Module Scaffold Checklist
When creating a new module, ensure it has:
```
Modules/MyModule/
├── app/Http/Controllers/MyModuleController.php
├── app/Http/Requests/StoreMyModuleRequest.php
├── app/Http/Requests/UpdateMyModuleRequest.php
├── app/Http/Resources/MyModuleResource.php
├── app/Models/MyModel.php                    (with SoftDeletes if needed)
├── app/Repositories/
│   ├── MyModuleRepositoryInterface.php
│   └── MyModuleRepository.php               (extends BaseRepository)
├── app/Providers/RouteServiceProvider.php
├── database/migrations/
├── routes/api.php
└── module.json
```

Enable with: `php artisan module:enable MyModule && php artisan optimize:clear`

## Repository Pattern

**Interface** (always inject this, not the concrete class):
```php
interface CourseRepositoryInterface extends RepositoryInterface
{
    public function getFiltered(array $filters, int $perPage): LengthAwarePaginator;
    public function findBySlug(string $slug, bool $publishedOnly = false): ?Course;
    public function toggleStatus(int $id): Course;
    public function syncCategories(int $courseId, array $categoryIds): void;
}
```

**Implementation**:
```php
class CourseRepository extends BaseRepository implements CourseRepositoryInterface
{
    public function __construct(Course $model)
    {
        parent::__construct($model);
    }

    public function getFiltered(array $filters, int $perPage): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, self::MAX_PER_PAGE));
        return $this->model->query()
            ->when($filters['search'] ?? null, fn($q, $s) => $q->where('name', 'like', "%$s%"))
            ->when($filters['level'] ?? null, fn($q, $l) => $q->where('level', $l))
            ->latest()
            ->paginate($perPage);
    }
}
```

**Binding** in module's `AppServiceProvider`:
```php
$this->app->bind(CourseRepositoryInterface::class, CourseRepository::class);
```

## Controller Pattern

```php
class AdminCourseController extends Controller
{
    public function __construct(private CourseRepositoryInterface $repository) {}

    public function index(Request $request): JsonResponse
    {
        $courses = $this->repository->getFiltered(
            $request->only(['search', 'level', 'category_id', 'status']),
            (int) $request->get('per_page', 15)
        );
        return $this->paginated($courses, CourseResource::class);
    }

    public function store(StoreCourseRequest $request): JsonResponse
    {
        $course = DB::transaction(function () use ($request) {
            $course = $this->repository->create($request->validated());
            $this->repository->syncCategories($course->id, $request->category_ids ?? []);
            return $course;
        });
        return $this->success(new CourseResource($course), 'Tạo khóa học thành công.', 201);
    }
}
```

## Form Request Pattern

```php
class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'         => 'required|string|max:255',
            'level'        => 'required|in:beginner,intermediate,advanced',
            'price'        => 'required|numeric|min:0',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ];
    }

    public function messages(): array
    {
        return ['name.required' => 'Tên khóa học là bắt buộc.'];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
```

## API Resource Pattern

```php
class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'price'       => $this->price,
            'sale_price'  => $this->sale_price,
            'level'       => $this->level,
            'status'      => $this->status,
            'thumbnail'   => $this->thumbnail ? asset('storage/' . $this->thumbnail) : null,
            'teacher'     => new TeacherResource($this->whenLoaded('teacher')),
            'categories'  => CategoryResource::collection($this->whenLoaded('categories')),
            'created_at'  => $this->created_at?->toISOString(),
        ];
    }
}
```

## Route Registration

```php
// routes/api.php in each module
Route::prefix('api/v1')->group(function () {
    // Public
    Route::get('courses', [CourseController::class, 'index']);

    // Admin
    Route::prefix('admin')->middleware(['auth:admin'])->group(function () {
        Route::get('courses', [AdminCourseController::class, 'index']);
        Route::post('courses', [AdminCourseController::class, 'store']);
        Route::patch('courses/{id}', [AdminCourseController::class, 'update']);
        Route::delete('courses/{id}', [AdminCourseController::class, 'destroy']);
        Route::patch('courses/{id}/toggle-status', [AdminCourseController::class, 'toggleStatus']);
        Route::get('courses/trashed', [AdminCourseController::class, 'trashed']);
        Route::delete('courses/bulk-delete', [AdminCourseController::class, 'bulkDelete']);
        Route::patch('courses/{id}/restore', [AdminCourseController::class, 'restore']);
    });

    // Student auth
    Route::middleware(['auth:api', 'email.verified'])->group(function () {
        Route::get('my-courses', [CourseController::class, 'myCourses']);
    });
});
```

## Common Artisan Commands

```bash
php artisan module:make MyModule
php artisan module:make-model MyModel MyModule
php artisan module:make-controller MyController MyModule
php artisan module:make-migration create_my_table MyModule
php artisan module:enable MyModule
php artisan optimize:clear
php artisan migrate
php artisan migrate:fresh --seed    # DEV ONLY — resets all data
php artisan test                    # run all feature/unit tests
```

## Feature Test Patterns

Auth setup in tests uses `forceCreate` (bypasses `$fillable` guard) + `actingAs`:

```php
// In test class — never use factories for simple auth setup
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

Use `Model::create([...])` directly for test data — no factory dependency needed for simple cases.

**HTTP method in tests must match API convention:**
- Update → `patchJson` (not `putJson`) — project uses PATCH, never PUT
- Soft delete → `deleteJson`
- Toggle/restore → `patchJson`

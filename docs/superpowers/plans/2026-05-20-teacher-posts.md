# Teacher Blog with Approval Workflow — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow teachers to write public blog posts via their portal, with an admin approval workflow before posts go live.

**Architecture:** Extend the existing Posts module — add `approval_status` + `rejection_reason` columns to `posts` table, add teacher-facing CRUD routes under `/teacher/posts`, add admin approve/reject endpoints, build teacher portal pages (list + form) and update admin PostsPage with approval filter and action buttons.

**Tech Stack:** Laravel 12, Nwidart Modules, Spatie Permission (`role:teacher` middleware), Vue 3 + TypeScript, @vueup/vue-quill, vue-toastification, Tailwind CSS

---

## File Map

**Created:**
- `Modules/Posts/database/migrations/2026_05_20_000001_add_approval_fields_to_posts_table.php`
- `Modules/Posts/app/Http/Controllers/Teacher/TeacherPostController.php`
- `Modules/Posts/app/Http/Requests/Teacher/StoreTeacherPostRequest.php`
- `Modules/Posts/app/Http/Requests/Teacher/UpdateTeacherPostRequest.php`
- `Modules/Posts/app/Http/Requests/Admin/RejectPostRequest.php`
- `tests/Feature/Admin/TeacherPostTest.php`
- `e-learning-frontend/src/composables/useTeacherPosts.ts`
- `e-learning-frontend/src/composables/useTeacherPostForm.ts`
- `e-learning-frontend/src/views/teacher/TeacherPostsPage.vue`
- `e-learning-frontend/src/views/teacher/TeacherPostFormPage.vue`

**Modified:**
- `Modules/Posts/app/Models/Post.php` — add to fillable
- `Modules/Posts/app/Http/Resources/PostResource.php` — add 2 fields
- `Modules/Posts/app/Repositories/PostRepositoryInterface.php` — add `getFilteredForTeacher()`
- `Modules/Posts/app/Repositories/PostRepository.php` — add `getFilteredForTeacher()` + update `getFiltered()`
- `Modules/Posts/app/Http/Controllers/Admin/PostController.php` — add `approve()`, `reject()`
- `Modules/Posts/routes/api.php` — add approve/reject + teacher routes
- `e-learning-frontend/src/services/post.service.ts` — add teacher + approve/reject methods
- `e-learning-frontend/src/views/admin/PostsPage.vue` — add approval filter + badges + action buttons
- `e-learning-frontend/src/router/index.js` — add 3 teacher post routes
- `e-learning-frontend/src/layouts/TeacherLayout.vue` — add Bài viết menu item

---

## Task 1: Migration + Model + Resource

**Files:**
- Create: `Modules/Posts/database/migrations/2026_05_20_000001_add_approval_fields_to_posts_table.php`
- Modify: `Modules/Posts/app/Models/Post.php`
- Modify: `Modules/Posts/app/Http/Resources/PostResource.php`

- [ ] **Step 1: Create migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])
                ->default('approved')
                ->after('is_published');
            $table->text('rejection_reason')->nullable()->after('approval_status');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['approval_status', 'rejection_reason']);
        });
    }
};
```

- [ ] **Step 2: Update Post model fillable**

In `Modules/Posts/app/Models/Post.php`, find the `$fillable` array and add `'approval_status'` and `'rejection_reason'`:

```php
protected $fillable = [
    'title',
    'slug',
    'content',
    'thumbnail',
    'author_id',
    'post_category_id',
    'is_published',
    'published_at',
    'views',
    'approval_status',
    'rejection_reason',
];
```

- [ ] **Step 3: Update PostResource**

In `Modules/Posts/app/Http/Resources/PostResource.php`, add these two fields to the `toArray()` return array (after `'is_published'`):

```php
'approval_status' => $this->approval_status,
'rejection_reason' => $this->rejection_reason,
```

- [ ] **Step 4: Run migration**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan migrate 2>&1" | cat
```

Expected: `Migrating: 2026_05_20_000001_add_approval_fields_to_posts_table` then `Migrated`.

- [ ] **Step 5: Verify existing tests still pass**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test tests/Feature/Admin/ 2>&1" | cat
```

Expected: all existing tests green (migration default `approved` is backward-compatible).

- [ ] **Step 6: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Posts/database/migrations/ e-learning-backend/Modules/Posts/app/Models/Post.php e-learning-backend/Modules/Posts/app/Http/Resources/PostResource.php && git commit -m 'feat(posts): add approval_status and rejection_reason fields to posts'" | cat
```

---

## Task 2: Repository update + Admin approve/reject endpoints

**Files:**
- Modify: `Modules/Posts/app/Repositories/PostRepositoryInterface.php`
- Modify: `Modules/Posts/app/Repositories/PostRepository.php`
- Modify: `Modules/Posts/app/Http/Controllers/Admin/PostController.php`
- Create: `Modules/Posts/app/Http/Requests/Admin/RejectPostRequest.php`
- Modify: `Modules/Posts/routes/api.php`
- Create: `tests/Feature/Admin/TeacherPostTest.php` (partial — admin tests only)

- [ ] **Step 1: Write failing tests for approve/reject**

Create `tests/Feature/Admin/TeacherPostTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Posts\Models\Post;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class TeacherPostTest extends TestCase
{
    use RefreshDatabase, HasAdminUser;

    private function createPost(array $attrs = []): Post
    {
        return Post::create(array_merge([
            'title'           => 'Test Post',
            'slug'            => 'test-post-' . uniqid(),
            'content'         => 'content',
            'author_id'       => 1,
            'approval_status' => 'pending',
            'is_published'    => false,
        ], $attrs));
    }

    public function test_admin_can_approve_pending_post(): void
    {
        $this->setupAdmin();
        $post = $this->createPost();

        $this->patchJson("/api/v1/admin/posts/{$post->id}/approve")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('posts', [
            'id'              => $post->id,
            'approval_status' => 'approved',
            'is_published'    => true,
        ]);
    }

    public function test_admin_can_reject_post_with_reason(): void
    {
        $this->setupAdmin();
        $post = $this->createPost();

        $this->patchJson("/api/v1/admin/posts/{$post->id}/reject", [
            'rejection_reason' => 'Nội dung không phù hợp.',
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('posts', [
            'id'               => $post->id,
            'approval_status'  => 'rejected',
            'is_published'     => false,
            'rejection_reason' => 'Nội dung không phù hợp.',
        ]);
    }

    public function test_reject_requires_reason(): void
    {
        $this->setupAdmin();
        $post = $this->createPost();

        $this->patchJson("/api/v1/admin/posts/{$post->id}/reject", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rejection_reason']);
    }

    public function test_admin_can_filter_posts_by_approval_status(): void
    {
        $this->setupAdmin();
        $this->createPost(['approval_status' => 'pending', 'slug' => 'pending-1']);
        $this->createPost(['approval_status' => 'approved', 'slug' => 'approved-1']);

        $res = $this->getJson('/api/v1/admin/posts?approval_status=pending');
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('pending', $res->json('data.0.approval_status'));
    }
}
```

- [ ] **Step 2: Run — confirm tests fail**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test tests/Feature/Admin/TeacherPostTest.php 2>&1" | cat
```

Expected: FAIL — `approve` method not found on controller.

- [ ] **Step 3: Add `approval_status` filter to PostRepository**

In `Modules/Posts/app/Repositories/PostRepository.php`, in the `getFiltered()` method, add this block after the `post_category_id` filter:

```php
if (! empty($filters['approval_status'])) {
    $query->where('approval_status', $filters['approval_status']);
}
```

- [ ] **Step 4: Add `getFilteredForTeacher()` to PostRepositoryInterface**

In `Modules/Posts/app/Repositories/PostRepositoryInterface.php`, add this method declaration:

```php
/**
 * Get paginated posts for a specific teacher (by author_id).
 */
public function getFilteredForTeacher(int $authorId, array $filters, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
```

- [ ] **Step 5: Implement `getFilteredForTeacher()` in PostRepository**

In `Modules/Posts/app/Repositories/PostRepository.php`, add this method after `getFiltered()`:

```php
public function getFilteredForTeacher(int $authorId, array $filters, int $perPage): LengthAwarePaginator
{
    $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

    $query = $this->model->newQuery()
        ->where('author_id', $authorId)
        ->with(['category', 'tags'])
        ->latest();

    if (! empty($filters['approval_status'])) {
        $query->where('approval_status', $filters['approval_status']);
    }

    return $query->paginate($perPage);
}
```

- [ ] **Step 6: Create `RejectPostRequest`**

Create `Modules/Posts/app/Http/Requests/Admin/RejectPostRequest.php`:

```php
<?php

namespace Modules\Posts\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RejectPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => 'required|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Vui lòng nhập lý do từ chối.',
            'rejection_reason.max'      => 'Lý do từ chối không quá 500 ký tự.',
        ];
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

- [ ] **Step 7: Add `approve()` and `reject()` to AdminPostController**

In `Modules/Posts/app/Http/Controllers/Admin/PostController.php`, add these imports at the top:

```php
use Modules\Posts\Http\Requests\Admin\RejectPostRequest;
```

Then add these two methods after `forceDelete()`:

```php
public function approve(int $id): JsonResponse
{
    $post = $this->repository->findOrFail($id);
    $post->update([
        'approval_status' => 'approved',
        'is_published'    => true,
        'published_at'    => $post->published_at ?? now(),
    ]);

    return $this->success(
        new PostResource($post->fresh(['author', 'category', 'tags'])),
        'Đã duyệt bài viết.'
    );
}

public function reject(RejectPostRequest $request, int $id): JsonResponse
{
    $post = $this->repository->findOrFail($id);
    $post->update([
        'approval_status'  => 'rejected',
        'is_published'     => false,
        'rejection_reason' => $request->rejection_reason,
    ]);

    return $this->success(
        new PostResource($post->fresh(['author', 'category', 'tags'])),
        'Đã từ chối bài viết.'
    );
}
```

- [ ] **Step 8: Add routes in `api.php`**

In `Modules/Posts/routes/api.php`, inside the `Route::prefix('v1/admin')->middleware(['auth:admin'])` group, add these two routes after the existing `posts/{id}/toggle-publish` line (they must appear BEFORE `posts/{id}` to avoid route conflict — but since they have extra path segments like `/approve`, they're distinct anyway):

```php
Route::patch('posts/{id}/approve', [AdminPostController::class, 'approve'])->middleware('permission:posts.edit');
Route::patch('posts/{id}/reject', [AdminPostController::class, 'reject'])->middleware('permission:posts.edit');
```

- [ ] **Step 9: Run tests — confirm pass**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test tests/Feature/Admin/TeacherPostTest.php 2>&1" | cat
```

Expected: 4/4 PASS.

- [ ] **Step 10: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Posts/ && git commit -m 'feat(posts): add admin approve/reject endpoints and approval_status filter'" | cat
```

---

## Task 3: TeacherPostController + teacher routes + tests

**Files:**
- Create: `Modules/Posts/app/Http/Controllers/Teacher/TeacherPostController.php`
- Create: `Modules/Posts/app/Http/Requests/Teacher/StoreTeacherPostRequest.php`
- Create: `Modules/Posts/app/Http/Requests/Teacher/UpdateTeacherPostRequest.php`
- Modify: `Modules/Posts/routes/api.php`
- Modify: `tests/Feature/Admin/TeacherPostTest.php` (add teacher CRUD tests)

- [ ] **Step 1: Add teacher CRUD tests to `TeacherPostTest.php`**

Append these test methods inside the class (after `test_admin_can_filter_posts_by_approval_status`):

```php
private function setupTeacher(string $email = 'teacher@test.com'): \Modules\Users\Models\User
{
    Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'admin']);
    $user = \Modules\Users\Models\User::forceCreate([
        'name'     => 'Teacher Test',
        'email'    => $email,
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('teacher');
    $this->actingAs($user, 'admin');
    return $user;
}

public function test_teacher_can_create_post(): void
{
    $this->setupTeacher();

    $res = $this->postJson('/api/v1/teacher/posts', [
        'title'            => 'My Teaching Post',
        'slug'             => 'my-teaching-post',
        'content'          => 'Educational content here.',
        'post_category_id' => null,
        'tag_ids'          => [],
    ]);

    $res->assertStatus(201)->assertJsonPath('success', true);
    $this->assertDatabaseHas('posts', [
        'slug'            => 'my-teaching-post',
        'approval_status' => 'pending',
        'is_published'    => false,
    ]);
}

public function test_teacher_sees_only_own_posts(): void
{
    $teacher1 = $this->setupTeacher('t1@test.com');
    $post1 = $this->createPost(['author_id' => $teacher1->id, 'slug' => 't1-post']);

    // Switch to teacher2
    $teacher2 = $this->setupTeacher('t2@test.com');
    $this->createPost(['author_id' => $teacher2->id, 'slug' => 't2-post']);

    $res = $this->getJson('/api/v1/teacher/posts');
    $res->assertStatus(200);
    $this->assertCount(1, $res->json('data'));
    $this->assertEquals('t2-post', $res->json('data.0.slug'));
}

public function test_teacher_cannot_view_another_teachers_post(): void
{
    $teacher1 = $this->setupTeacher('t1@test.com');
    $post = $this->createPost(['author_id' => $teacher1->id, 'slug' => 't1-own']);

    $this->setupTeacher('t2@test.com');
    $this->getJson("/api/v1/teacher/posts/{$post->id}")
        ->assertStatus(403);
}

public function test_teacher_can_update_own_post(): void
{
    $teacher = $this->setupTeacher();
    $post = $this->createPost(['author_id' => $teacher->id]);

    $this->patchJson("/api/v1/teacher/posts/{$post->id}", [
        'title'   => 'Updated Title',
        'content' => 'Updated content.',
    ])
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('posts', [
        'id'    => $post->id,
        'title' => 'Updated Title',
    ]);
}

public function test_teacher_can_delete_own_post(): void
{
    $teacher = $this->setupTeacher();
    $post = $this->createPost(['author_id' => $teacher->id]);

    $this->deleteJson("/api/v1/teacher/posts/{$post->id}")
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->assertSoftDeleted('posts', ['id' => $post->id]);
}

public function test_non_teacher_cannot_access_teacher_routes(): void
{
    $this->setupAdmin(); // super-admin, not teacher role only
    // Actually super-admin has ALL roles by convention — create a plain admin instead
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
    $admin = \Modules\Users\Models\User::forceCreate([
        'name'     => 'Plain Admin',
        'email'    => 'plain_admin@test.com',
        'password' => bcrypt('password'),
    ]);
    $admin->assignRole('admin');
    $this->actingAs($admin, 'admin');

    $this->getJson('/api/v1/teacher/posts')
        ->assertStatus(403);
}
```

- [ ] **Step 2: Run tests — confirm teacher tests fail**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test tests/Feature/Admin/TeacherPostTest.php 2>&1" | cat
```

Expected: 4 pass (admin tests), 6 fail (teacher routes 404).

- [ ] **Step 3: Create `StoreTeacherPostRequest`**

Create `Modules/Posts/app/Http/Requests/Teacher/StoreTeacherPostRequest.php`:

```php
<?php

namespace Modules\Posts\Http\Requests\Teacher;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreTeacherPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'            => 'required|string|max:255',
            'slug'             => 'required|string|max:255|unique:posts,slug|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            'content'          => 'required|string',
            'post_category_id' => 'nullable|exists:post_categories,id',
            'tag_ids'          => 'nullable|array',
            'tag_ids.*'        => 'exists:tags,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'   => 'Tiêu đề không được để trống.',
            'title.max'        => 'Tiêu đề không quá 255 ký tự.',
            'slug.required'    => 'Slug không được để trống.',
            'slug.unique'      => 'Slug này đã được sử dụng.',
            'slug.regex'       => 'Slug chỉ được chứa chữ thường, số và dấu gạch ngang.',
            'content.required' => 'Nội dung không được để trống.',
        ];
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

- [ ] **Step 4: Create `UpdateTeacherPostRequest`**

Create `Modules/Posts/app/Http/Requests/Teacher/UpdateTeacherPostRequest.php`:

```php
<?php

namespace Modules\Posts\Http\Requests\Teacher;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateTeacherPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'            => 'sometimes|required|string|max:255',
            'slug'             => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('posts', 'slug')->ignore((int) $this->route('id')),
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ],
            'content'          => 'sometimes|required|string',
            'post_category_id' => 'nullable|exists:post_categories,id',
            'tag_ids'          => 'nullable|array',
            'tag_ids.*'        => 'exists:tags,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'   => 'Tiêu đề không được để trống.',
            'slug.unique'      => 'Slug này đã được sử dụng.',
            'slug.regex'       => 'Slug chỉ được chứa chữ thường, số và dấu gạch ngang.',
            'content.required' => 'Nội dung không được để trống.',
        ];
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

- [ ] **Step 5: Create `TeacherPostController`**

Create `Modules/Posts/app/Http/Controllers/Teacher/TeacherPostController.php`:

```php
<?php

namespace Modules\Posts\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Posts\Http\Requests\Teacher\StoreTeacherPostRequest;
use Modules\Posts\Http\Requests\Teacher\UpdateTeacherPostRequest;
use Modules\Posts\Http\Resources\PostResource;
use Modules\Posts\Repositories\PostRepositoryInterface;

class TeacherPostController extends Controller
{
    use ApiResponse;

    public function __construct(private PostRepositoryInterface $repository) {}

    private function authorId(): int
    {
        return auth('admin')->id();
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $posts = $this->repository->getFilteredForTeacher(
            $this->authorId(),
            $request->only(['approval_status']),
            $perPage
        );
        $posts->setCollection(PostResource::collection($posts->getCollection())->collection);

        return $this->paginated($posts, 'Lấy danh sách bài viết thành công.');
    }

    public function store(StoreTeacherPostRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'author_id'       => $this->authorId(),
            'approval_status' => 'pending',
            'is_published'    => false,
        ]);

        $post = DB::transaction(function () use ($data, $request) {
            $post = $this->repository->create($data);
            if (! empty($data['tag_ids'])) {
                $post->tags()->sync($data['tag_ids']);
            }
            return $post;
        });

        return $this->success(
            new PostResource($post->load(['category', 'tags'])),
            'Bài viết đã được gửi chờ duyệt.',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $post = $this->repository->findOrFail($id);
        abort_if($post->author_id !== $this->authorId(), 403, 'Bạn không có quyền xem bài viết này.');

        return $this->success(new PostResource($post->load(['category', 'tags'])));
    }

    public function update(UpdateTeacherPostRequest $request, int $id): JsonResponse
    {
        $post = $this->repository->findOrFail($id);
        abort_if($post->author_id !== $this->authorId(), 403, 'Bạn không có quyền sửa bài viết này.');

        DB::transaction(function () use ($request, $post) {
            $this->repository->update($post->id, $request->validated());
            if ($request->has('tag_ids')) {
                $post->tags()->sync($request->tag_ids ?? []);
            }
        });

        return $this->success(
            new PostResource($post->fresh(['category', 'tags'])),
            'Cập nhật bài viết thành công.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $post = $this->repository->findOrFail($id);
        abort_if($post->author_id !== $this->authorId(), 403, 'Bạn không có quyền xóa bài viết này.');
        $this->repository->delete($id);

        return $this->success(null, 'Đã xóa bài viết.');
    }
}
```

- [ ] **Step 6: Add teacher routes to `api.php`**

In `Modules/Posts/routes/api.php`, add this import at the top with other use statements:

```php
use Modules\Posts\Http\Controllers\Teacher\TeacherPostController;
```

Then add this route group at the bottom of the file (after the existing client auth routes):

```php
// Teacher portal — posts
Route::prefix('v1/teacher')->middleware(['auth:admin', 'role:teacher'])->group(function () {
    Route::get('posts', [TeacherPostController::class, 'index']);
    Route::post('posts', [TeacherPostController::class, 'store']);
    Route::get('posts/{id}', [TeacherPostController::class, 'show']);
    Route::patch('posts/{id}', [TeacherPostController::class, 'update']);
    Route::delete('posts/{id}', [TeacherPostController::class, 'destroy']);
});
```

- [ ] **Step 7: Run all TeacherPostTest tests**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test tests/Feature/Admin/TeacherPostTest.php 2>&1" | cat
```

Expected: 10/10 PASS.

- [ ] **Step 8: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Posts/ tests/Feature/Admin/TeacherPostTest.php && git commit -m 'feat(posts): add TeacherPostController with CRUD and tests'" | cat
```

---

## Task 4: Frontend — post.service.ts additions

**Files:**
- Modify: `e-learning-frontend/src/services/post.service.ts`

- [ ] **Step 1: Add methods to `PostService`**

In `e-learning-frontend/src/services/post.service.ts`, add these methods to the `PostService` object (after `storeComment`):

```ts
  // Admin — approve/reject
  approvePost(id: number) {
    return axios.patch(`/admin/posts/${id}/approve`)
  },
  rejectPost(id: number, data: { rejection_reason: string }) {
    return axios.patch(`/admin/posts/${id}/reject`, data)
  },

  // Teacher portal — own posts
  getTeacherPosts(params?: Record<string, unknown>) {
    return axios.get('/teacher/posts', { params })
  },
  createTeacherPost(data: Record<string, unknown>) {
    return axios.post('/teacher/posts', data)
  },
  getTeacherPost(id: number | string) {
    return axios.get(`/teacher/posts/${id}`)
  },
  updateTeacherPost(id: number | string, data: Record<string, unknown>) {
    return axios.patch(`/teacher/posts/${id}`, data)
  },
  deleteTeacherPost(id: number) {
    return axios.delete(`/teacher/posts/${id}`)
  },
```

- [ ] **Step 2: Lint check**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run lint 2>&1 | grep -E 'error|warning' | head -20" | cat
```

Expected: no new errors.

- [ ] **Step 3: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-frontend/src/services/post.service.ts && git commit -m 'feat(frontend): add teacher post and admin approve/reject methods to PostService'" | cat
```

---

## Task 5: Admin PostsPage.vue — approval filter + approve/reject UI

**Files:**
- Modify: `e-learning-frontend/src/views/admin/PostsPage.vue`

First read the file: `cat e-learning-frontend/src/views/admin/PostsPage.vue`

Then make these specific changes:

- [ ] **Step 1: Add approval_status to composable filter and table**

The existing `PostsPage.vue` has a `usePosts()` composable. Read the current file, then:

**In the `<template>` filter section** (where search input and category/status dropdowns are), add this dropdown after the existing status filter:

```html
<select
  v-model="approvalFilter"
  @change="fetchPosts()"
  class="border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm rounded-lg px-3 py-2 focus:outline-none"
>
  <option value="">Tất cả duyệt</option>
  <option value="pending">Chờ duyệt</option>
  <option value="approved">Đã duyệt</option>
  <option value="rejected">Từ chối</option>
</select>
```

**In the table header `<tr>`**, add a new `<th>` for approval status after the existing Status column:

```html
<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Duyệt</th>
```

**In the table body `<tr>` for each post**, add the approval status cell after the existing status cell:

```html
<td class="px-4 py-3 text-sm">
  <span
    :class="[
      'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
      post.approval_status === 'approved'
        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
        : post.approval_status === 'rejected'
        ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
        : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    ]"
  >
    {{ post.approval_status === 'approved' ? 'Đã duyệt' : post.approval_status === 'rejected' ? 'Từ chối' : 'Chờ duyệt' }}
  </span>
</td>
```

**In the actions cell** (where Edit/Delete buttons are), add approve/reject buttons for pending posts:

```html
<template v-if="post.approval_status === 'pending'">
  <button
    @click="handleApprove(post.id)"
    class="text-green-600 hover:text-green-800 dark:text-green-400 text-xs font-medium"
  >
    Duyệt
  </button>
  <button
    @click="openRejectModal(post)"
    class="text-red-600 hover:text-red-800 dark:text-red-400 text-xs font-medium ml-2"
  >
    Từ chối
  </button>
</template>
```

- [ ] **Step 2: Add reject modal at the bottom of `<template>`**

Before the closing `</template>`, add:

```html
<!-- Reject Modal -->
<div
  v-if="showRejectModal"
  class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
>
  <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Từ chối bài viết</h3>
    <textarea
      v-model="rejectReason"
      rows="3"
      placeholder="Nhập lý do từ chối..."
      class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
    />
    <div class="flex justify-end gap-3 mt-4">
      <button
        @click="showRejectModal = false"
        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400"
      >
        Hủy
      </button>
      <button
        @click="handleReject()"
        :disabled="!rejectReason.trim()"
        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg font-medium disabled:opacity-50"
      >
        Xác nhận từ chối
      </button>
    </div>
  </div>
</div>
```

- [ ] **Step 3: Add script logic**

In the `<script setup>` section, add these imports and refs:

```ts
import PostService from '@/services/post.service'
import { useToast } from 'vue-toastification'

const toast = useToast()
const approvalFilter = ref('')
const showRejectModal = ref(false)
const rejectReason = ref('')
const rejectTargetId = ref<number | null>(null)
```

Update the `fetchPosts()` call to pass `approval_status`:

Find the existing `fetchPosts()` call and ensure `approvalFilter` is passed. Check the existing composable's filter API and add `approval_status: approvalFilter.value` to the params.

In `usePosts()` or the inline fetch, pass `approval_status: approvalFilter.value` as a filter param.

Add these functions:

```ts
async function handleApprove(id: number) {
  try {
    await PostService.approvePost(id)
    toast.success('Đã duyệt bài viết.')
    fetchPosts()
  } catch {
    toast.error('Không thể duyệt bài viết.')
  }
}

function openRejectModal(post: { id: number }) {
  rejectTargetId.value = post.id
  rejectReason.value = ''
  showRejectModal.value = true
}

async function handleReject() {
  if (!rejectTargetId.value || !rejectReason.value.trim()) return
  try {
    await PostService.rejectPost(rejectTargetId.value, { rejection_reason: rejectReason.value })
    toast.success('Đã từ chối bài viết.')
    showRejectModal.value = false
    fetchPosts()
  } catch {
    toast.error('Không thể từ chối bài viết.')
  }
}
```

- [ ] **Step 4: Check `usePosts` passes `approval_status` filter**

Read `e-learning-frontend/src/composables/usePosts.ts`. If `fetchPosts()` accepts a params object or the composable exposes filters, ensure `approval_status` is included. If `usePosts` uses its own reactive `filters` object, add `approval_status` to it and bind `approvalFilter` to that. Adapt to the actual composable API.

- [ ] **Step 5: Build check**

```bash
wsl.exe -d Ubuntu -- bash -c "source /home/vanthanh/.nvm/nvm.sh && cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run build 2>&1 | tail -5" | cat
```

Expected: `✓ built in X.XXs`

- [ ] **Step 6: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-frontend/src/views/admin/PostsPage.vue && git commit -m 'feat(frontend): add approval filter and approve/reject actions to admin PostsPage'" | cat
```

---

## Task 6: Teacher composables + pages + router + TeacherLayout

**Files:**
- Create: `e-learning-frontend/src/composables/useTeacherPosts.ts`
- Create: `e-learning-frontend/src/composables/useTeacherPostForm.ts`
- Create: `e-learning-frontend/src/views/teacher/TeacherPostsPage.vue`
- Create: `e-learning-frontend/src/views/teacher/TeacherPostFormPage.vue`
- Modify: `e-learning-frontend/src/router/index.js`
- Modify: `e-learning-frontend/src/layouts/TeacherLayout.vue`

- [ ] **Step 1: Create `useTeacherPosts.ts`**

```ts
import { ref, reactive } from 'vue'
import { useToast } from 'vue-toastification'
import PostService from '@/services/post.service'

interface TeacherPost {
  id: number
  title: string
  slug: string
  approval_status: 'pending' | 'approved' | 'rejected'
  rejection_reason: string | null
  is_published: boolean
  category: { id: number; name: string; slug: string } | null
  created_at: string
}

interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export function useTeacherPosts() {
  const posts = ref<TeacherPost[]>([])
  const pagination = ref<Pagination>({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
  const loading = ref(false)
  const filters = reactive({ page: 1, per_page: 15, approval_status: '' })
  const toast = useToast()

  async function fetchPosts() {
    loading.value = true
    try {
      const params: Record<string, unknown> = { page: filters.page, per_page: filters.per_page }
      if (filters.approval_status) params.approval_status = filters.approval_status
      const res = await PostService.getTeacherPosts(params)
      posts.value = res.data.data
      pagination.value = res.data.pagination
    } finally {
      loading.value = false
    }
  }

  async function deletePost(id: number) {
    if (!confirm('Bạn có chắc muốn xóa bài viết này?')) return
    try {
      await PostService.deleteTeacherPost(id)
      toast.success('Đã xóa bài viết.')
      await fetchPosts()
    } catch {
      toast.error('Không thể xóa bài viết.')
    }
  }

  function changePage(page: number) {
    filters.page = page
    fetchPosts()
  }

  return { posts, pagination, loading, filters, fetchPosts, deletePost, changePage }
}
```

- [ ] **Step 2: Create `useTeacherPostForm.ts`**

```ts
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import PostService from '@/services/post.service'

interface PostCategory { id: number; name: string; slug: string }
interface Tag { id: number; name: string; slug: string }

export function useTeacherPostForm() {
  const route = useRoute()
  const router = useRouter()
  const toast = useToast()

  const postId = computed(() => (route.params.id ? Number(route.params.id) : null))
  const isEdit = computed(() => !!postId.value)

  const categories = ref<PostCategory[]>([])
  const tags = ref<Tag[]>([])
  const loading = ref(false)
  const saving = ref(false)
  const errors = ref<Record<string, string[]>>({})

  const form = reactive({
    title: '',
    slug: '',
    content: '',
    post_category_id: null as number | null,
    tag_ids: [] as number[],
  })

  function autoSlug(title: string): string {
    return title
      .toLowerCase()
      .replace(/[àáâãäåạảấầẩẫậắằẳẵặ]/g, 'a')
      .replace(/[èéêëẹẻẽếềểễệ]/g, 'e')
      .replace(/[ìíîïịỉĩ]/g, 'i')
      .replace(/[òóôõöọỏốồổỗộớờởỡợ]/g, 'o')
      .replace(/[ùúûüụủũứừửữự]/g, 'u')
      .replace(/[ýỳỵỷỹ]/g, 'y')
      .replace(/[đ]/g, 'd')
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .trim()
  }

  function onTitleChange(title: string) {
    if (!isEdit.value) {
      form.slug = autoSlug(title)
    }
  }

  async function loadForm() {
    loading.value = true
    try {
      const [catRes, tagRes] = await Promise.all([
        PostService.getClientCategories(),
        PostService.getClientTags(),
      ])
      categories.value = catRes.data.data
      tags.value = tagRes.data.data

      if (isEdit.value) {
        const res = await PostService.getTeacherPost(postId.value!)
        const post = res.data.data
        form.title = post.title
        form.slug = post.slug
        form.content = post.content ?? ''
        form.post_category_id = post.category?.id ?? null
        form.tag_ids = post.tags?.map((t: Tag) => t.id) ?? []
      }
    } finally {
      loading.value = false
    }
  }

  async function submit() {
    saving.value = true
    errors.value = {}
    try {
      if (isEdit.value) {
        await PostService.updateTeacherPost(postId.value!, { ...form })
        toast.success('Đã cập nhật bài viết.')
      } else {
        await PostService.createTeacherPost({ ...form })
        toast.success('Bài viết đã được gửi chờ duyệt.')
      }
      router.push('/teacher/posts')
    } catch (err: unknown) {
      const e = err as { response?: { data?: { errors?: Record<string, string[]>; message?: string } } }
      errors.value = e.response?.data?.errors ?? {}
      toast.error(e.response?.data?.message ?? 'Có lỗi xảy ra.')
    } finally {
      saving.value = false
    }
  }

  onMounted(() => loadForm())

  return { form, categories, tags, loading, saving, errors, isEdit, onTitleChange, submit }
}
```

- [ ] **Step 3: Create `TeacherPostsPage.vue`**

```vue
<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Bài viết của tôi</h1>
      <router-link
        to="/teacher/posts/create"
        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors"
      >
        + Viết bài mới
      </router-link>
    </div>

    <div class="mb-4">
      <select
        v-model="filters.approval_status"
        @change="fetchPosts()"
        class="border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none"
      >
        <option value="">Tất cả trạng thái</option>
        <option value="pending">Chờ duyệt</option>
        <option value="approved">Đã duyệt</option>
        <option value="rejected">Từ chối</option>
      </select>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
          <tr>
            <th class="px-5 py-3 text-left font-semibold">Tiêu đề</th>
            <th class="px-5 py-3 text-left font-semibold">Danh mục</th>
            <th class="px-5 py-3 text-center font-semibold">Trạng thái</th>
            <th class="px-5 py-3 text-left font-semibold">Ngày tạo</th>
            <th class="px-5 py-3 text-center font-semibold">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="5" class="px-5 py-10 text-center text-gray-400">Đang tải...</td>
          </tr>
          <tr v-else-if="!posts.length">
            <td colspan="5" class="px-5 py-10 text-center text-gray-400">Chưa có bài viết nào.</td>
          </tr>
          <tr
            v-for="post in posts"
            :key="post.id"
            class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50"
          >
            <td class="px-5 py-3">
              <p class="font-medium text-gray-900 dark:text-white">{{ post.title }}</p>
              <p class="text-xs text-gray-400">{{ post.slug }}</p>
              <p v-if="post.rejection_reason" class="text-xs text-red-500 mt-1">
                Lý do từ chối: {{ post.rejection_reason }}
              </p>
            </td>
            <td class="px-5 py-3 text-gray-600 dark:text-gray-400">
              {{ post.category?.name ?? '—' }}
            </td>
            <td class="px-5 py-3 text-center">
              <span
                :class="[
                  'inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium',
                  post.approval_status === 'approved'
                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                    : post.approval_status === 'rejected'
                    ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                    : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                ]"
              >
                {{
                  post.approval_status === 'approved'
                    ? 'Đã duyệt'
                    : post.approval_status === 'rejected'
                    ? 'Từ chối'
                    : 'Chờ duyệt'
                }}
              </span>
            </td>
            <td class="px-5 py-3 text-gray-500 text-xs">
              {{ new Date(post.created_at).toLocaleDateString('vi-VN') }}
            </td>
            <td class="px-5 py-3 text-center">
              <div class="flex items-center justify-center gap-2">
                <router-link
                  :to="`/teacher/posts/${post.id}/edit`"
                  class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-xs font-medium"
                >
                  Sửa
                </router-link>
                <button
                  @click="deletePost(post.id)"
                  class="text-red-600 hover:text-red-800 dark:text-red-400 text-xs font-medium"
                >
                  Xóa
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div
        v-if="pagination.last_page > 1"
        class="px-5 py-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-sm text-gray-500"
      >
        <span>Tổng {{ pagination.total }} bài viết</span>
        <div class="flex gap-1">
          <button
            v-for="page in pagination.last_page"
            :key="page"
            @click="changePage(page)"
            :class="[
              'px-3 py-1 rounded text-sm',
              page === pagination.current_page
                ? 'bg-blue-600 text-white'
                : 'hover:bg-gray-100 dark:hover:bg-gray-700',
            ]"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useTeacherPosts } from '@/composables/useTeacherPosts'

const { posts, pagination, loading, filters, fetchPosts, deletePost, changePage } = useTeacherPosts()
onMounted(() => fetchPosts())
</script>
```

- [ ] **Step 4: Create `TeacherPostFormPage.vue`**

```vue
<template>
  <div class="max-w-4xl">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
      {{ isEdit ? 'Chỉnh sửa bài viết' : 'Viết bài mới' }}
    </h1>

    <div v-if="loading" class="text-center py-12 text-gray-400">Đang tải...</div>

    <template v-else>
      <div
        v-if="!isEdit"
        class="mb-5 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg text-sm text-blue-700 dark:text-blue-300"
      >
        Bài viết sẽ được Admin xét duyệt trước khi đăng công khai.
      </div>

      <div class="space-y-5">
        <!-- Title -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Tiêu đề <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.title"
            @input="onTitleChange(form.title)"
            type="text"
            placeholder="Nhập tiêu đề bài viết..."
            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <p v-if="errors.title" class="text-red-500 text-xs mt-1">{{ errors.title[0] }}</p>
        </div>

        <!-- Slug (read-only) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug</label>
          <input
            :value="form.slug"
            type="text"
            readonly
            class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 rounded-lg px-3 py-2 text-sm bg-gray-50 cursor-not-allowed"
          />
          <p v-if="errors.slug" class="text-red-500 text-xs mt-1">{{ errors.slug[0] }}</p>
        </div>

        <!-- Content (Quill) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Nội dung <span class="text-red-500">*</span>
          </label>
          <QuillEditor
            v-model:content="form.content"
            contentType="html"
            theme="snow"
            style="min-height: 300px"
            class="bg-white dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600"
          />
          <p v-if="errors.content" class="text-red-500 text-xs mt-1">{{ errors.content[0] }}</p>
        </div>

        <!-- Category -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Danh mục</label>
          <select
            v-model="form.post_category_id"
            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option :value="null">-- Không có danh mục --</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
        </div>

        <!-- Tags -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tags</label>
          <div class="flex flex-wrap gap-3">
            <label
              v-for="tag in tags"
              :key="tag.id"
              class="flex items-center gap-1.5 cursor-pointer"
            >
              <input
                type="checkbox"
                :value="tag.id"
                v-model="form.tag_ids"
                class="rounded text-blue-600 focus:ring-blue-500"
              />
              <span class="text-sm text-gray-700 dark:text-gray-300">{{ tag.name }}</span>
            </label>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-3 pt-2">
          <button
            @click="submit"
            :disabled="saving"
            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-50"
          >
            {{ saving ? 'Đang lưu...' : isEdit ? 'Lưu thay đổi' : 'Gửi duyệt' }}
          </button>
          <router-link
            to="/teacher/posts"
            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
          >
            Hủy
          </router-link>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'
import { useTeacherPostForm } from '@/composables/useTeacherPostForm'

const { form, categories, tags, loading, saving, errors, isEdit, onTitleChange, submit } =
  useTeacherPostForm()
</script>
```

- [ ] **Step 5: Update router — add 3 teacher post routes**

In `e-learning-frontend/src/router/index.js`, inside the `/teacher` route group children array, add these 3 routes (place `posts/create` before `posts/:id/edit`):

```js
{
  path: 'posts',
  name: 'teacher.posts',
  component: () => import('@/views/teacher/TeacherPostsPage.vue'),
},
{
  path: 'posts/create',
  name: 'teacher.posts.create',
  component: () => import('@/views/teacher/TeacherPostFormPage.vue'),
},
{
  path: 'posts/:id/edit',
  name: 'teacher.posts.edit',
  component: () => import('@/views/teacher/TeacherPostFormPage.vue'),
},
```

- [ ] **Step 6: Update `TeacherLayout.vue` — add Bài viết menu item**

In `e-learning-frontend/src/layouts/TeacherLayout.vue`, add `PageIcon` to the import from `@/components/icons`:

```ts
import {
  GridIcon,
  BoxCubeIcon,
  BarChartIcon,
  UserCircleIcon,
  LogoutIcon,
  PageIcon,
} from '@/components/icons'
```

Then add this item to the `menuItems` array (after Khóa học, before Thu nhập):

```ts
{ name: 'Bài viết', path: '/teacher/posts', icon: PageIcon },
```

- [ ] **Step 7: Build check**

```bash
wsl.exe -d Ubuntu -- bash -c "source /home/vanthanh/.nvm/nvm.sh && cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run build 2>&1 | tail -5" | cat
```

Expected: `✓ built in X.XXs` with no errors.

- [ ] **Step 8: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-frontend/src/composables/useTeacherPosts.ts e-learning-frontend/src/composables/useTeacherPostForm.ts e-learning-frontend/src/views/teacher/TeacherPostsPage.vue e-learning-frontend/src/views/teacher/TeacherPostFormPage.vue e-learning-frontend/src/router/index.js e-learning-frontend/src/layouts/TeacherLayout.vue && git commit -m 'feat(frontend): add teacher post pages with composables and router routes'" | cat
```

---

## Self-Review

**1. Spec coverage:**
- ✅ `approval_status` migration with default `approved` (backward compat) — Task 1
- ✅ Admin approve endpoint — Task 2
- ✅ Admin reject endpoint with `rejection_reason` — Task 2
- ✅ `?approval_status` filter on `GET /admin/posts` — Task 2
- ✅ Teacher CRUD `/teacher/posts` — Task 3
- ✅ Ownership check (403 if not author) — Task 3 (TeacherPostController)
- ✅ Teacher portal: list page with status badges + rejection reason shown — Task 6
- ✅ Teacher portal: create/edit form with Quill editor — Task 6
- ✅ Auto-slug from title (read-only in form) — Task 6 (`useTeacherPostForm`)
- ✅ Info banner "chờ duyệt" on create form — Task 6 (TeacherPostFormPage)
- ✅ Admin PostsPage approval filter + approve/reject buttons — Task 5
- ✅ Reject modal with reason — Task 5
- ✅ TeacherLayout "Bài viết" menu item — Task 6
- ✅ No category/tag management for teachers (read-only select from existing) — by design

**2. Placeholder scan:** No TBD/TODO in any step. All code is complete.

**3. Type consistency:**
- `TeacherPost.approval_status` → `'pending' | 'approved' | 'rejected'` — consistent across composable, page template, and backend enum
- `PostService.rejectPost(id, { rejection_reason })` — matches `RejectPostRequest.rules()` key name
- `getFilteredForTeacher(authorId, filters, perPage)` — declared in interface and implemented in repository with same signature

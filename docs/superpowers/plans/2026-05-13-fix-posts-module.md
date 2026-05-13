# Fix Posts Module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix 12 issues in the Posts module found by code review — wrong model import, missing DB::transaction, guard mismatches, inline validation, missing failedValidation(), bad paginator usage, route method errors, and missing trashed/restore/force-delete endpoints.

**Architecture:** All fixes stay inside `Modules/Posts/`. New FormRequests are created for bulk-delete validation. Trashed routes reuse BaseRepository methods (`paginateTrashed`, `restore`, `forceDeleteById`) which are already implemented. No new interfaces or repositories needed.

**Tech Stack:** Laravel 12, Nwidart Modules, Spatie Permission, BaseRepository (app/Repositories/BaseRepository.php), ApiResponse trait, Pint (code style)

---

## File Map

| File | Action | Reason |
|------|--------|--------|
| `Modules/Posts/app/Models/PostComment.php` | Modify | Wrong `App\Models\User` import |
| `Modules/Posts/app/Http/Requests/Admin/StorePostRequest.php` | Modify | Add `failedValidation()` + `messages()` |
| `Modules/Posts/app/Http/Requests/Admin/UpdatePostRequest.php` | Modify | Add `failedValidation()` + `messages()` |
| `Modules/Posts/app/Http/Requests/Admin/StorePostCategoryRequest.php` | Modify | Add `failedValidation()` + `messages()` |
| `Modules/Posts/app/Http/Requests/Admin/UpdatePostCategoryRequest.php` | Modify | Add `failedValidation()` + `messages()` |
| `Modules/Posts/app/Http/Requests/Admin/StoreTagRequest.php` | Modify | Add `failedValidation()` + `messages()` |
| `Modules/Posts/app/Http/Requests/Admin/UpdateTagRequest.php` | Modify | Add `failedValidation()` + `messages()` |
| `Modules/Posts/app/Http/Requests/Client/StoreCommentRequest.php` | Modify | Add `failedValidation()` + `messages()` |
| `Modules/Posts/app/Http/Requests/Admin/BulkDeletePostsRequest.php` | **Create** | Extract inline validation from `PostController::bulkDelete()` |
| `Modules/Posts/app/Http/Requests/Admin/BulkDeleteCommentsRequest.php` | **Create** | Extract inline validation from `CommentController::bulkDelete()` |
| `Modules/Posts/app/Http/Controllers/Admin/PostController.php` | Modify | Fix `auth('admin')`, `DB::transaction`, use FormRequest, add trashed/restore/forceDelete methods |
| `Modules/Posts/app/Http/Controllers/Admin/CommentController.php` | Modify | Fix paginator pattern, use FormRequest for bulkDelete |
| `Modules/Posts/app/Http/Controllers/Client/CommentController.php` | Modify | Fix `auth('api')->id()` |
| `Modules/Posts/app/Http/Resources/PostResource.php` | Modify | Use `whenLoaded()` for `author` |
| `Modules/Posts/routes/api.php` | Modify | DELETE bulk-delete, PATCH-only update, add trashed/restore/force-delete routes, fix route order |
| `tests/Feature/Admin/PostTest.php` | Modify | `putJson` → `patchJson` |

---

### Task 1: Fix PostComment wrong User model import

**Files:**
- Modify: `Modules/Posts/app/Models/PostComment.php:3`

- [ ] **Step 1: Fix the import**

Replace `use App\Models\User;` with `use Modules\Users\Models\User;` in `PostComment.php`:

```php
<?php

namespace Modules\Posts\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Students\Models\Student;
use Modules\Users\Models\User;

class PostComment extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',
        'user_type',
        'content',
        'parent_id',
        'is_approved',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function adminUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'user_id');
    }

    public function getCommenterAttribute()
    {
        if ($this->user_type === 'student') {
            return $this->student;
        }

        return $this->adminUser;
    }

    public function parent()
    {
        return $this->belongsTo(PostComment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(PostComment::class, 'parent_id');
    }
}
```

- [ ] **Step 2: Run tests to confirm no regression**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test tests/Feature/Admin/PostTest.php tests/Feature/Client/PostTest.php 2>&1" | cat
```

Expected: tests pass.

- [ ] **Step 3: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Posts/app/Models/PostComment.php && git commit -m 'fix(post): fix PostComment wrong User model import'" | cat
```

---

### Task 2: Add failedValidation() and messages() to all Admin FormRequests

**Files:**
- Modify: `Modules/Posts/app/Http/Requests/Admin/StorePostRequest.php`
- Modify: `Modules/Posts/app/Http/Requests/Admin/UpdatePostRequest.php`
- Modify: `Modules/Posts/app/Http/Requests/Admin/StorePostCategoryRequest.php`
- Modify: `Modules/Posts/app/Http/Requests/Admin/UpdatePostCategoryRequest.php`
- Modify: `Modules/Posts/app/Http/Requests/Admin/StoreTagRequest.php`
- Modify: `Modules/Posts/app/Http/Requests/Admin/UpdateTagRequest.php`

- [ ] **Step 1: Rewrite StorePostRequest**

```php
<?php

namespace Modules\Posts\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'            => 'required|string|max:255',
            'slug'             => 'required|string|max:255|unique:posts,slug',
            'content'          => 'required|string',
            'thumbnail'        => 'nullable|string',
            'post_category_id' => 'nullable|exists:post_categories,id',
            'is_published'     => 'boolean',
            'tag_ids'          => 'nullable|array',
            'tag_ids.*'        => 'exists:tags,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'            => 'Tiêu đề bài viết là bắt buộc.',
            'slug.required'             => 'Slug là bắt buộc.',
            'slug.unique'               => 'Slug đã tồn tại.',
            'content.required'          => 'Nội dung bài viết là bắt buộc.',
            'post_category_id.exists'   => 'Danh mục không tồn tại.',
            'tag_ids.*.exists'          => 'Tag không tồn tại.',
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

- [ ] **Step 2: Rewrite UpdatePostRequest**

```php
<?php

namespace Modules\Posts\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'title'            => 'sometimes|required|string|max:255',
            'slug'             => 'sometimes|required|string|max:255|unique:posts,slug,'.$id,
            'content'          => 'sometimes|required|string',
            'thumbnail'        => 'nullable|string',
            'post_category_id' => 'nullable|exists:post_categories,id',
            'is_published'     => 'boolean',
            'tag_ids'          => 'nullable|array',
            'tag_ids.*'        => 'exists:tags,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'          => 'Tiêu đề bài viết là bắt buộc.',
            'slug.required'           => 'Slug là bắt buộc.',
            'slug.unique'             => 'Slug đã tồn tại.',
            'content.required'        => 'Nội dung bài viết là bắt buộc.',
            'post_category_id.exists' => 'Danh mục không tồn tại.',
            'tag_ids.*.exists'        => 'Tag không tồn tại.',
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

- [ ] **Step 3: Rewrite StorePostCategoryRequest**

```php
<?php

namespace Modules\Posts\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePostCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'slug'        => 'required|string|max:255|unique:post_categories,slug',
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên danh mục là bắt buộc.',
            'slug.required' => 'Slug là bắt buộc.',
            'slug.unique'   => 'Slug danh mục đã tồn tại.',
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

- [ ] **Step 4: Rewrite UpdatePostCategoryRequest**

```php
<?php

namespace Modules\Posts\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePostCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name'        => 'sometimes|required|string|max:255',
            'slug'        => 'sometimes|required|string|max:255|unique:post_categories,slug,'.$id,
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên danh mục là bắt buộc.',
            'slug.required' => 'Slug là bắt buộc.',
            'slug.unique'   => 'Slug danh mục đã tồn tại.',
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

- [ ] **Step 5: Rewrite StoreTagRequest**

```php
<?php

namespace Modules\Posts\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tags,slug',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên tag là bắt buộc.',
            'slug.required' => 'Slug là bắt buộc.',
            'slug.unique'   => 'Slug tag đã tồn tại.',
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

- [ ] **Step 6: Rewrite UpdateTagRequest**

```php
<?php

namespace Modules\Posts\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:tags,slug,'.$id,
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên tag là bắt buộc.',
            'slug.required' => 'Slug là bắt buộc.',
            'slug.unique'   => 'Slug tag đã tồn tại.',
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

- [ ] **Step 7: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Posts/app/Http/Requests/Admin/ && git commit -m 'refactor(post): add failedValidation and Vietnamese messages to all Admin FormRequests'" | cat
```

---

### Task 3: Add failedValidation() and messages() to Client StoreCommentRequest

**Files:**
- Modify: `Modules/Posts/app/Http/Requests/Client/StoreCommentRequest.php`

- [ ] **Step 1: Rewrite StoreCommentRequest**

```php
<?php

namespace Modules\Posts\Http\Requests\Client;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content'   => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:post_comments,id',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required'  => 'Nội dung bình luận là bắt buộc.',
            'content.max'       => 'Nội dung bình luận không được vượt quá 1000 ký tự.',
            'parent_id.exists'  => 'Bình luận cha không tồn tại.',
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

- [ ] **Step 2: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Posts/app/Http/Requests/Client/StoreCommentRequest.php && git commit -m 'refactor(post): add failedValidation and messages to StoreCommentRequest'" | cat
```

---

### Task 4: Create BulkDeletePostsRequest and BulkDeleteCommentsRequest

**Files:**
- Create: `Modules/Posts/app/Http/Requests/Admin/BulkDeletePostsRequest.php`
- Create: `Modules/Posts/app/Http/Requests/Admin/BulkDeleteCommentsRequest.php`

- [ ] **Step 1: Create BulkDeletePostsRequest**

Create file `e-learning-backend/Modules/Posts/app/Http/Requests/Admin/BulkDeletePostsRequest.php`:

```php
<?php

namespace Modules\Posts\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BulkDeletePostsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:posts,id',
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required'  => 'Danh sách bài viết là bắt buộc.',
            'ids.array'     => 'Danh sách bài viết phải là mảng.',
            'ids.*.exists'  => 'Một số bài viết không tồn tại.',
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

- [ ] **Step 2: Create BulkDeleteCommentsRequest**

Create file `e-learning-backend/Modules/Posts/app/Http/Requests/Admin/BulkDeleteCommentsRequest.php`:

```php
<?php

namespace Modules\Posts\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BulkDeleteCommentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:post_comments,id',
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required'  => 'Danh sách bình luận là bắt buộc.',
            'ids.array'     => 'Danh sách bình luận phải là mảng.',
            'ids.*.exists'  => 'Một số bình luận không tồn tại.',
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

- [ ] **Step 3: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Posts/app/Http/Requests/Admin/BulkDeletePostsRequest.php e-learning-backend/Modules/Posts/app/Http/Requests/Admin/BulkDeleteCommentsRequest.php && git commit -m 'refactor(post): extract BulkDeletePostsRequest and BulkDeleteCommentsRequest'" | cat
```

---

### Task 5: Fix PostResource author — use whenLoaded()

**Files:**
- Modify: `Modules/Posts/app/Http/Resources/PostResource.php`

- [ ] **Step 1: Rewrite PostResource**

```php
<?php

namespace Modules\Posts\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'slug'          => $this->slug,
            'content'       => $this->content,
            'thumbnail'     => $this->thumbnail,
            'thumbnail_url' => $this->thumbnail ? asset('storage/'.$this->thumbnail) : null,
            'author'        => $this->whenLoaded('author', fn () => [
                'id'   => $this->author->id,
                'name' => $this->author->name,
            ]),
            'category'     => new PostCategoryResource($this->whenLoaded('category')),
            'tags'         => TagResource::collection($this->whenLoaded('tags')),
            'comments'     => PostCommentResource::collection($this->whenLoaded('comments')),
            'is_published' => $this->is_published,
            'published_at' => $this->published_at?->toISOString(),
            'views'        => $this->views,
            'created_at'   => $this->created_at?->toISOString(),
            'updated_at'   => $this->updated_at?->toISOString(),
            'deleted_at'   => $this->deleted_at?->toISOString(),
        ];
    }
}
```

- [ ] **Step 2: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Posts/app/Http/Resources/PostResource.php && git commit -m 'fix(post): use whenLoaded for author in PostResource'" | cat
```

---

### Task 6: Fix Admin PostController — guard, transaction, FormRequest, add trashed methods

**Files:**
- Modify: `Modules/Posts/app/Http/Controllers/Admin/PostController.php`

- [ ] **Step 1: Rewrite Admin/PostController.php**

```php
<?php

namespace Modules\Posts\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Posts\Http\Requests\Admin\BulkDeletePostsRequest;
use Modules\Posts\Http\Requests\Admin\StorePostRequest;
use Modules\Posts\Http\Requests\Admin\UpdatePostRequest;
use Modules\Posts\Http\Resources\PostResource;
use Modules\Posts\Repositories\PostRepositoryInterface;

class PostController extends Controller
{
    use ApiResponse;

    public function __construct(
        private PostRepositoryInterface $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $posts = $this->repository->getFiltered(
            $request->all(),
            (int) $request->query('per_page', 15)
        );

        $posts->setCollection(PostResource::collection($posts->getCollection())->collection);

        return $this->paginated($posts, 'Lấy danh sách bài viết thành công.');
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['author_id'] = auth('admin')->id();

        if (! empty($data['is_published'])) {
            $data['published_at'] = now();
        }

        $post = DB::transaction(function () use ($data) {
            $post = $this->repository->create($data);

            if (! empty($data['tag_ids'])) {
                $post->tags()->sync($data['tag_ids']);
            }

            return $post;
        });

        return $this->success(
            new PostResource($post->load(['author', 'category', 'tags'])),
            'Tạo bài viết thành công.',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $post = $this->repository->findOrFail($id);
        $post->load(['author', 'category', 'tags']);

        return $this->success(
            new PostResource($post),
            'Lấy thông tin bài viết thành công.'
        );
    }

    public function update(UpdatePostRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $post = $this->repository->findOrFail($id);

        if (isset($data['is_published']) && $data['is_published'] && ! $post->is_published && ! $post->published_at) {
            $data['published_at'] = now();
        }

        $post = DB::transaction(function () use ($data, $id) {
            $post = $this->repository->update($id, $data);

            if (isset($data['tag_ids'])) {
                $post->tags()->sync($data['tag_ids']);
            }

            return $post;
        });

        return $this->success(
            new PostResource($post->load(['author', 'category', 'tags'])),
            'Cập nhật bài viết thành công.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->success(null, 'Xóa bài viết thành công.');
    }

    public function togglePublish(int $id): JsonResponse
    {
        $post = $this->repository->togglePublish($id);

        return $this->success(
            new PostResource($post->load(['author', 'category', 'tags'])),
            'Thay đổi trạng thái xuất bản thành công.'
        );
    }

    public function bulkDelete(BulkDeletePostsRequest $request): JsonResponse
    {
        $deleted = $this->repository->deleteMany($request->ids);

        return $this->success(
            ['deleted_count' => $deleted, 'deleted_ids' => $request->ids],
            "Đã xoá {$deleted} bài viết thành công."
        );
    }

    public function trashed(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $data = $this->repository->paginateTrashed($perPage, ['*'], ['author', 'category', 'tags']);
        $data->setCollection(PostResource::collection($data->getCollection())->collection);

        return $this->paginated($data, 'Lấy danh sách bài viết đã xóa thành công.');
    }

    public function restore(int $id): JsonResponse
    {
        $this->repository->restore($id);

        return $this->success(null, 'Khôi phục bài viết thành công.');
    }

    public function forceDelete(int $id): JsonResponse
    {
        $this->repository->forceDeleteById($id);

        return $this->success(null, 'Xóa vĩnh viễn bài viết thành công.');
    }
}
```

- [ ] **Step 2: Run tests**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test tests/Feature/Admin/PostTest.php 2>&1" | cat
```

Expected: tests pass.

- [ ] **Step 3: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Posts/app/Http/Controllers/Admin/PostController.php && git commit -m 'refactor(post): fix auth guard, add DB::transaction, use BulkDeletePostsRequest, add trashed/restore/forceDelete'" | cat
```

---

### Task 7: Fix Admin CommentController — paginator pattern + FormRequest

**Files:**
- Modify: `Modules/Posts/app/Http/Controllers/Admin/CommentController.php`

- [ ] **Step 1: Rewrite Admin/CommentController.php**

```php
<?php

namespace Modules\Posts\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Posts\Http\Requests\Admin\BulkDeleteCommentsRequest;
use Modules\Posts\Http\Resources\PostCommentResource;
use Modules\Posts\Repositories\CommentRepositoryInterface;

class CommentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CommentRepositoryInterface $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $comments = $this->repository->getFiltered(
            $request->all(),
            (int) $request->query('per_page', 15)
        );

        $comments->setCollection(PostCommentResource::collection($comments->getCollection())->collection);

        return $this->paginated($comments, 'Lấy danh sách bình luận thành công.');
    }

    public function toggleApproval(int $id): JsonResponse
    {
        $comment = $this->repository->toggleApproval($id);

        return $this->success(
            new PostCommentResource($comment->load(['post', 'adminUser', 'student'])),
            'Thay đổi trạng thái duyệt bình luận thành công.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->success(null, 'Xóa bình luận thành công.');
    }

    public function bulkDelete(BulkDeleteCommentsRequest $request): JsonResponse
    {
        $deleted = $this->repository->deleteMany($request->ids);

        return $this->success(
            ['deleted_count' => $deleted, 'deleted_ids' => $request->ids],
            "Đã xoá {$deleted} bình luận thành công."
        );
    }
}
```

- [ ] **Step 2: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Posts/app/Http/Controllers/Admin/CommentController.php && git commit -m 'refactor(post): fix CommentController paginator pattern, use BulkDeleteCommentsRequest'" | cat
```

---

### Task 8: Fix Client CommentController — auth guard

**Files:**
- Modify: `Modules/Posts/app/Http/Controllers/Client/CommentController.php`

- [ ] **Step 1: Fix auth guard**

Change `$data['user_id'] = auth()->id();` to `$data['user_id'] = auth('api')->id();`:

```php
<?php

namespace Modules\Posts\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Posts\Http\Requests\Client\StoreCommentRequest;
use Modules\Posts\Http\Resources\PostCommentResource;
use Modules\Posts\Repositories\CommentRepositoryInterface;

class CommentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CommentRepositoryInterface $repository
    ) {}

    public function store(StoreCommentRequest $request, int $postId): JsonResponse
    {
        $data = $request->validated();
        $data['post_id'] = $postId;
        $data['user_id'] = auth('api')->id();
        $data['user_type'] = 'student';
        $data['is_approved'] = true;

        $comment = $this->repository->create($data);

        return $this->success(
            new PostCommentResource($comment->load('student')),
            'Đăng bình luận thành công.',
            201
        );
    }
}
```

- [ ] **Step 2: Run client tests**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test tests/Feature/Client/PostTest.php 2>&1" | cat
```

Expected: all client post tests pass.

- [ ] **Step 3: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Posts/app/Http/Controllers/Client/CommentController.php && git commit -m 'fix(post): use auth(api) guard in Client CommentController'" | cat
```

---

### Task 9: Fix routes — DELETE bulk-delete, PATCH-only update, add trashed/restore/forceDelete

**Files:**
- Modify: `Modules/Posts/routes/api.php`

- [ ] **Step 1: Rewrite routes/api.php**

Important: static routes (`trashed`, `bulk-delete`) MUST appear before parameterized routes (`{id}`).

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\Posts\Http\Controllers\Admin\CommentController as AdminCommentController;
use Modules\Posts\Http\Controllers\Admin\PostCategoryController as AdminPostCategoryController;
use Modules\Posts\Http\Controllers\Admin\PostController as AdminPostController;
use Modules\Posts\Http\Controllers\Admin\TagController as AdminTagController;
use Modules\Posts\Http\Controllers\Client\CommentController;
use Modules\Posts\Http\Controllers\Client\PostController;
use Modules\Posts\Http\Controllers\Admin\PostCategoryController;
use Modules\Posts\Http\Controllers\Admin\TagController;

Route::prefix('v1/admin')->middleware(['auth:admin'])->group(function () {
    // Post Categories
    Route::get('post-categories', [AdminPostCategoryController::class, 'index'])->middleware('permission:posts.view');
    Route::post('post-categories', [AdminPostCategoryController::class, 'store'])->middleware('permission:posts.create');
    Route::get('post-categories/{id}', [AdminPostCategoryController::class, 'show'])->middleware('permission:posts.view');
    Route::patch('post-categories/{id}', [AdminPostCategoryController::class, 'update'])->middleware('permission:posts.edit');
    Route::delete('post-categories/{id}', [AdminPostCategoryController::class, 'destroy'])->middleware('permission:posts.delete');

    // Tags
    Route::get('tags', [AdminTagController::class, 'index'])->middleware('permission:tags.view');
    Route::post('tags', [AdminTagController::class, 'store'])->middleware('permission:tags.create');
    Route::get('tags/{id}', [AdminTagController::class, 'show'])->middleware('permission:tags.view');
    Route::patch('tags/{id}', [AdminTagController::class, 'update'])->middleware('permission:tags.edit');
    Route::delete('tags/{id}', [AdminTagController::class, 'destroy'])->middleware('permission:tags.delete');

    // Posts — static routes BEFORE parameterized
    Route::get('posts/trashed', [AdminPostController::class, 'trashed'])->middleware('permission:posts.view');
    Route::delete('posts/bulk-delete', [AdminPostController::class, 'bulkDelete'])->middleware('permission:posts.delete');
    Route::get('posts', [AdminPostController::class, 'index'])->middleware('permission:posts.view');
    Route::post('posts', [AdminPostController::class, 'store'])->middleware('permission:posts.create');
    Route::get('posts/{id}', [AdminPostController::class, 'show'])->middleware('permission:posts.view');
    Route::patch('posts/{id}', [AdminPostController::class, 'update'])->middleware('permission:posts.edit');
    Route::delete('posts/{id}', [AdminPostController::class, 'destroy'])->middleware('permission:posts.delete');
    Route::patch('posts/{id}/toggle-publish', [AdminPostController::class, 'togglePublish'])->middleware('permission:posts.edit');
    Route::patch('posts/{id}/restore', [AdminPostController::class, 'restore'])->middleware('permission:posts.edit');
    Route::delete('posts/{id}/force-delete', [AdminPostController::class, 'forceDelete'])->middleware('permission:posts.delete');

    // Comments — static routes BEFORE parameterized
    Route::delete('comments/bulk-delete', [AdminCommentController::class, 'bulkDelete'])->middleware('permission:comments.delete');
    Route::get('comments', [AdminCommentController::class, 'index'])->middleware('permission:comments.view');
    Route::patch('comments/{id}/toggle-approval', [AdminCommentController::class, 'toggleApproval'])->middleware('permission:comments.delete');
    Route::delete('comments/{id}', [AdminCommentController::class, 'destroy'])->middleware('permission:comments.delete');
});

// Client Public Routes
Route::prefix('v1')->group(function () {
    Route::get('posts', [PostController::class, 'index']);
    Route::get('posts/{slug}', [PostController::class, 'show']);
    Route::post('posts/{id}/increment-views', [PostController::class, 'incrementViews']);

    Route::get('post-categories', [PostCategoryController::class, 'index']);
    Route::get('tags', [TagController::class, 'index']);
});

// Client Auth Routes
Route::prefix('v1')->middleware(['auth:api'])->group(function () {
    Route::post('posts/{id}/comments', [CommentController::class, 'store']);
});
```

- [ ] **Step 2: Run all post tests**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test tests/Feature/Admin/PostTest.php tests/Feature/Client/PostTest.php 2>&1" | cat
```

Expected: all tests pass.

- [ ] **Step 3: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Posts/routes/api.php && git commit -m 'refactor(post): DELETE bulk-delete, PATCH-only update, add trashed/restore/force-delete routes'" | cat
```

---

### Task 10: Fix tests and run full suite

**Files:**
- Modify: `tests/Feature/Admin/PostTest.php`

- [ ] **Step 1: Fix putJson → patchJson in test_update_post_success**

In `tests/Feature/Admin/PostTest.php` line 71, change:
```php
$response = $this->putJson($this->baseUrl.'/'.$post->id, [
```
to:
```php
$response = $this->patchJson($this->baseUrl.'/'.$post->id, [
```

- [ ] **Step 2: Run full test suite**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test 2>&1" | cat
```

Expected: all tests pass (look for any regressions in other modules).

- [ ] **Step 3: Run Pint**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && ./vendor/bin/pint 2>&1" | cat
```

Expected: "No files were changed." or auto-fixes applied.

- [ ] **Step 4: If Pint changed files, run tests again**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test 2>&1" | cat
```

- [ ] **Step 5: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/tests/Feature/Admin/PostTest.php && git commit -m 'test(post): fix putJson to patchJson in update test'" | cat
```

If Pint changed files, stage and commit those separately:

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Posts/ && git commit -m 'chore(post): pint formatting fixes'" | cat
```

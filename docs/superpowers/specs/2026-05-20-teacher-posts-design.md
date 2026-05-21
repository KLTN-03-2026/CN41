# Teacher Blog with Approval Workflow — Design Spec

## Goal

Allow teachers to write public blog posts via their portal. Posts go through an admin approval workflow before appearing on the public site. Teachers can edit their posts at any time without re-triggering approval.

## Architecture

Reuse the existing `Posts` module (backend + frontend). Add:
1. A new `approval_status` column on the `posts` table (backward-compatible default: `approved`)
2. Teacher-facing routes for their own posts (create, read, update, delete)
3. Two new admin actions: approve and reject
4. Teacher portal pages: post list + create/edit form
5. Admin panel updates: approval status column, filter, and approve/reject buttons

---

## Database

### Migration: add `approval_status` to `posts`

```php
$table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved');
$table->text('rejection_reason')->nullable();
```

- Default `approved` ensures all existing admin posts are unaffected.
- When a **teacher** creates a post: `approval_status = 'pending'`, `is_published = false`
- When **admin approves**: `approval_status = 'approved'`, `is_published = true`
- When **admin rejects**: `approval_status = 'rejected'`, `is_published = false`, `rejection_reason = '<text>'`
- **Teacher edits an existing post**: content updates immediately; `approval_status` does NOT reset. A previously-approved post stays public after editing.

---

## Backend

### New: `TeacherPostController` in `Modules/Posts/`

Routes (under `auth:admin + role:teacher` middleware):

| Method | URL | Action |
|--------|-----|--------|
| GET | `/teacher/posts` | List own posts (paginated, filterable by `approval_status`) |
| POST | `/teacher/posts` | Create post → auto-sets `author_id`, `approval_status=pending`, `is_published=false` |
| GET | `/teacher/posts/{id}` | Show own post (404 if not author) |
| PATCH | `/teacher/posts/{id}` | Update own post (404 if not author) |
| DELETE | `/teacher/posts/{id}` | Soft-delete own post (404 if not author) |

Ownership check: every action verifies `post->author_id === auth('admin')->id()`.

### Updated: `AdminPostController`

Two new actions (under existing `auth:admin` middleware):

| Method | URL | Action |
|--------|-----|--------|
| PATCH | `/admin/posts/{id}/approve` | Set `approval_status=approved`, `is_published=true` |
| PATCH | `/admin/posts/{id}/reject` | Set `approval_status=rejected`, `is_published=false`, store `rejection_reason` |

Existing `GET /admin/posts` gains optional `?approval_status=pending|approved|rejected` filter.

### Form Requests

- `StoreTeacherPostRequest` — validates: `title` (required|string|max:255), `content` (required|string), `post_category_id` (nullable|exists), `tag_ids` (nullable|array|exists:tags,id)
- `UpdateTeacherPostRequest` — same rules, all nullable for partial update
- `RejectPostRequest` — validates: `rejection_reason` (required|string|max:500)

Slug is auto-generated from title on create (unique, teacher cannot set it manually).

### Post Resource update

`PostResource` gains two new fields:
```php
'approval_status' => $this->approval_status,
'rejection_reason' => $this->rejection_reason,
```

---

## Frontend — Teacher Portal

### TeacherLayout: add "Bài viết" menu item

```ts
{ name: 'Bài viết', path: '/teacher/posts', icon: PageIcon }
```

### New routes in `/teacher` group

```js
{ path: 'posts', name: 'teacher.posts', component: () => import('@/views/teacher/TeacherPostsPage.vue') },
{ path: 'posts/create', name: 'teacher.posts.create', component: () => import('@/views/teacher/TeacherPostFormPage.vue') },
{ path: 'posts/:id/edit', name: 'teacher.posts.edit', component: () => import('@/views/teacher/TeacherPostFormPage.vue') },
```

### New service methods in `post.service.ts`

```ts
// Teacher portal
getTeacherPosts: (params) => http.get('/teacher/posts', { params }),
createTeacherPost: (data) => http.post('/teacher/posts', data),
getTeacherPost: (id) => http.get(`/teacher/posts/${id}`),
updateTeacherPost: (id, data) => http.patch(`/teacher/posts/${id}`, data),
deleteTeacherPost: (id) => http.delete(`/teacher/posts/${id}`),
```

If `post.service.ts` does not exist, create it. If admin post methods are in another file, follow that convention.

### New composables

- `useTeacherPosts.ts` — list with pagination + delete
- `useTeacherPostForm.ts` — create/edit form logic, loads categories and tags from existing public endpoints

### New pages

**`TeacherPostsPage.vue`**
- Table: Title | Category | Status badge | Created date | Actions (Edit / Delete)
- Status badges: "Chờ duyệt" (yellow), "Đã duyệt" (green), "Từ chối" (red)
- Rejected posts show rejection reason on hover/tooltip
- "Viết bài mới" button → `/teacher/posts/create`

**`TeacherPostFormPage.vue`** (create + edit, same component)
- Fields: Title (input), Content (Quill rich-text editor), Category (dropdown), Tags (multi-select)
- Slug: auto-generated, displayed read-only
- Submit button: "Gửi duyệt" on create, "Lưu thay đổi" on edit
- Info banner: "Bài viết sẽ được Admin xét duyệt trước khi đăng công khai"

---

## Frontend — Admin Panel

### Updated `PostsPage.vue`

- Add "Trạng thái duyệt" column showing `approval_status` badge (chỉ hiển thị khi bài không phải của admin)
- Add filter dropdown: `Tất cả | Chờ duyệt | Đã duyệt | Từ chối`
- For posts with `approval_status = 'pending'`: show Approve ✓ and Reject ✗ action buttons
- Reject opens a modal with `rejection_reason` textarea

### New service methods (admin side)

```ts
approvePost: (id) => http.patch(`/admin/posts/${id}/approve`),
rejectPost: (id, data) => http.patch(`/admin/posts/${id}/reject`, data),
```

---

## Permissions

Teachers do NOT get Spatie permission entries for posts. Access is controlled at the route level by `role:teacher` middleware + ownership checks in the controller. This avoids polluting the permission system with per-user ownership rules.

---

## What is NOT included (YAGNI)

- Teachers cannot create/edit/delete categories or tags
- No email notification when admin approves/rejects (out of scope)
- No re-approval trigger when teacher edits an approved post
- No image upload for post thumbnail in teacher portal (admin-only feature)
- No comment moderation for teacher's posts (admin handles it)

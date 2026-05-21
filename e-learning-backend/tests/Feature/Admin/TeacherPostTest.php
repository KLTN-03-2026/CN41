<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Posts\Models\Post;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class TeacherPostTest extends TestCase
{
    use HasAdminUser, RefreshDatabase;

    private function createPost(array $attrs = []): Post
    {
        return Post::create(array_merge([
            'title' => 'Test Post',
            'slug' => 'test-post-'.uniqid(),
            'content' => 'content',
            'author_id' => 1,
            'approval_status' => 'pending',
            'is_published' => false,
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
            'id' => $post->id,
            'approval_status' => 'approved',
            'is_published' => true,
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
            'id' => $post->id,
            'approval_status' => 'rejected',
            'is_published' => false,
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

    private function setupTeacher(string $email = 'teacher@test.com'): User
    {
        Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'admin']);
        $user = User::forceCreate([
            'name' => 'Teacher Test',
            'email' => $email,
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
            'title' => 'My Teaching Post',
            'slug' => 'my-teaching-post',
            'content' => 'Educational content here.',
            'post_category_id' => null,
            'tag_ids' => [],
        ]);

        $res->assertStatus(201)->assertJsonPath('success', true);
        $this->assertDatabaseHas('posts', [
            'slug' => 'my-teaching-post',
            'approval_status' => 'pending',
            'is_published' => false,
        ]);
    }

    public function test_teacher_sees_only_own_posts(): void
    {
        $teacher1 = $this->setupTeacher('t1@test.com');
        $this->createPost(['author_id' => $teacher1->id, 'slug' => 't1-post']);

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
            'title' => 'Updated Title',
            'content' => 'Updated content.',
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
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
        // Create a plain admin (not teacher role)
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        $admin = User::forceCreate([
            'name' => 'Plain Admin',
            'email' => 'plain_admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');
        $this->actingAs($admin, 'admin');

        $this->getJson('/api/v1/teacher/posts')
            ->assertStatus(403);
    }
}

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

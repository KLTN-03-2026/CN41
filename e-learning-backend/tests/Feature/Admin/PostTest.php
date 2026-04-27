<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Posts\Models\Post;
use Modules\Posts\Models\PostCategory;
use Modules\Users\Models\User;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = '/api/v1/admin/posts';

    protected function setupAdmin()
    {
        $admin = User::forceCreate([
            'name' => 'Admin Test',
            'email' => 'admin_post_test@test.com',
            'password' => 'password123',
        ]);

        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_posts_index_returns_success()
    {
        $admin = $this->setupAdmin();
        $category = PostCategory::create(['name' => 'Tech', 'slug' => 'tech']);

        Post::create([
            'title' => 'Post 1',
            'slug' => 'post-1',
            'content' => 'Content 1',
            'author_id' => $admin->id,
            'post_category_id' => $category->id,
            'is_published' => true,
        ]);

        $response = $this->getJson($this->baseUrl);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_create_post_success()
    {
        $this->setupAdmin();
        $category = PostCategory::create(['name' => 'Tech', 'slug' => 'tech']);

        $response = $this->postJson($this->baseUrl, [
            'title' => 'New Post',
            'slug' => 'new-post',
            'content' => 'This is a new post',
            'post_category_id' => $category->id,
            'is_published' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('posts', [
            'title' => 'New Post',
            'slug' => 'new-post',
        ]);
    }

    public function test_update_post_success()
    {
        $admin = $this->setupAdmin();
        $category = PostCategory::create(['name' => 'Tech', 'slug' => 'tech']);
        $post = Post::create([
            'title' => 'Old Title',
            'slug' => 'old-title',
            'content' => 'Old content',
            'author_id' => $admin->id,
            'post_category_id' => $category->id,
            'is_published' => false,
        ]);

        $response = $this->putJson($this->baseUrl.'/'.$post->id, [
            'title' => 'New Title',
            'slug' => 'new-title',
            'content' => 'New content',
            'post_category_id' => $category->id,
            'is_published' => true,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'New Title',
            'slug' => 'new-title',
            'is_published' => 1,
        ]);
    }

    public function test_delete_post()
    {
        $admin = $this->setupAdmin();
        $post = Post::create([
            'title' => 'To Delete',
            'slug' => 'to-delete',
            'content' => 'Content',
            'author_id' => $admin->id,
            'is_published' => false,
        ]);

        $response = $this->deleteJson($this->baseUrl.'/'.$post->id);

        $response->assertStatus(200);
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }
}

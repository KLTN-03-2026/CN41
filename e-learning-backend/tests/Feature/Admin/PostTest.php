<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Posts\Models\Post;
use Modules\Posts\Models\PostCategory;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase, \Tests\Traits\HasAdminUser;

    private string $baseUrl = '/api/v1/admin/posts';

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

        $response = $this->patchJson($this->baseUrl.'/'.$post->id, [
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

    public function test_create_post_fails_without_required_fields()
    {
        $this->setupAdmin();

        $response = $this->postJson($this->baseUrl, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'slug', 'content']);
    }

    public function test_create_post_fails_with_duplicate_slug()
    {
        $admin = $this->setupAdmin();
        Post::create([
            'title' => 'First Post',
            'slug' => 'dup-slug',
            'content' => 'Content',
            'author_id' => $admin->id,
            'is_published' => false,
        ]);

        $response = $this->postJson($this->baseUrl, [
            'title' => 'Second Post',
            'slug' => 'dup-slug',
            'content' => 'Content',
            'is_published' => false,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_trashed_returns_deleted_posts()
    {
        $admin = $this->setupAdmin();
        $post = Post::create([
            'title' => 'Trashed Post',
            'slug' => 'trashed-post',
            'content' => 'Content',
            'author_id' => $admin->id,
            'is_published' => false,
        ]);
        $post->delete();

        $response = $this->getJson($this->baseUrl.'/trashed');

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Trashed Post']);
    }

    public function test_restore_post_success()
    {
        $admin = $this->setupAdmin();
        $post = Post::create([
            'title' => 'Restore Post',
            'slug' => 'restore-post',
            'content' => 'Content',
            'author_id' => $admin->id,
            'is_published' => false,
        ]);
        $post->delete();

        $response = $this->patchJson($this->baseUrl.'/'.$post->id.'/restore');

        $response->assertStatus(200);
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'deleted_at' => null]);
    }

    public function test_force_delete_post_success()
    {
        $admin = $this->setupAdmin();
        $post = Post::create([
            'title' => 'Force Delete Post',
            'slug' => 'force-delete-post',
            'content' => 'Content',
            'author_id' => $admin->id,
            'is_published' => false,
        ]);
        $post->delete();

        $response = $this->deleteJson($this->baseUrl.'/'.$post->id.'/force-delete');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_toggle_publish_success()
    {
        $admin = $this->setupAdmin();
        $post = Post::create([
            'title' => 'Draft Post',
            'slug' => 'draft-post',
            'content' => 'Content',
            'author_id' => $admin->id,
            'is_published' => false,
        ]);

        $response = $this->patchJson($this->baseUrl.'/'.$post->id.'/toggle-publish');

        $response->assertStatus(200);
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'is_published' => 1]);

        $this->patchJson($this->baseUrl.'/'.$post->id.'/toggle-publish');
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'is_published' => 0]);
    }
}

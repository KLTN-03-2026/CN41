<?php

namespace Tests\Feature\Client;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Posts\Models\Post;
use Modules\Students\Models\Student;
use Modules\Users\Models\User;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = '/api/v1/posts';

    protected function setupStudent()
    {
        $student = Student::forceCreate([
            'name' => 'Student Test',
            'email' => 'student_post_test@test.com',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($student, 'api');

        return $student;
    }

    protected function setupAdmin()
    {
        $admin = User::forceCreate([
            'name' => 'Admin Test',
            'email' => 'admin_post_test@test.com',
            'password' => bcrypt('password123'),
        ]);

        return $admin;
    }

    public function test_client_can_view_published_posts()
    {
        $admin = $this->setupAdmin();

        // Published post
        Post::create([
            'title' => 'Published Post',
            'slug' => 'published-post',
            'content' => 'Content',
            'author_id' => $admin->id,
            'is_published' => true,
            'published_at' => now(),
        ]);

        // Unpublished post
        Post::create([
            'title' => 'Draft Post',
            'slug' => 'draft-post',
            'content' => 'Content',
            'author_id' => $admin->id,
            'is_published' => false,
        ]);

        $response = $this->getJson($this->baseUrl);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Published Post');
    }

    public function test_client_can_view_post_details_and_increment_views()
    {
        $admin = $this->setupAdmin();
        $post = Post::create([
            'title' => 'View Test',
            'slug' => 'view-test',
            'content' => 'Content',
            'author_id' => $admin->id,
            'is_published' => true,
            'views' => 10,
        ]);

        $response = $this->getJson($this->baseUrl.'/view-test');

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'View Test');

        // Increment views
        $incrementResponse = $this->postJson($this->baseUrl.'/'.$post->id.'/increment-views');
        $incrementResponse->assertStatus(200);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'views' => 11,
        ]);
    }

    public function test_student_can_comment_on_post()
    {
        $student = $this->setupStudent();
        $admin = $this->setupAdmin();

        $post = Post::create([
            'title' => 'Comment Test',
            'slug' => 'comment-test',
            'content' => 'Content',
            'author_id' => $admin->id,
            'is_published' => true,
        ]);

        $response = $this->postJson($this->baseUrl.'/'.$post->id.'/comments', [
            'content' => 'This is a test comment.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('post_comments', [
            'post_id' => $post->id,
            'user_id' => $student->id,
            'user_type' => 'student',
            'content' => 'This is a test comment.',
        ]);
    }

    public function test_guest_cannot_comment_on_post()
    {
        $admin = $this->setupAdmin();

        $post = Post::create([
            'title' => 'Guest Comment Test',
            'slug' => 'guest-comment-test',
            'content' => 'Content',
            'author_id' => $admin->id,
            'is_published' => true,
        ]);

        $response = $this->postJson($this->baseUrl.'/'.$post->id.'/comments', [
            'content' => 'This is a test comment.',
        ]);

        $response->assertStatus(401);
    }
}

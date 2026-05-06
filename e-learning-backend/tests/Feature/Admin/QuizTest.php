<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Lessons\Models\Lesson;
use Modules\Quiz\Models\Quiz;
use Modules\Users\Models\User;
use Tests\TestCase;

class QuizTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::forceCreate([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($this->admin, 'admin');
    }

    public function test_admin_can_list_quizzes()
    {
        $lesson = Lesson::factory()->create();
        $quiz = Quiz::factory()->create(['lesson_id' => $lesson->id]);

        $response = $this->getJson('/api/v1/admin/quizzes');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('pagination.total', 1);
    }

    public function test_admin_can_create_quiz()
    {
        $lesson = Lesson::factory()->create();

        $response = $this->postJson('/api/v1/admin/quizzes', [
            'lesson_id' => $lesson->id,
            'title' => 'Quiz Test',
            'description' => 'Test quiz',
            'max_attempts' => 3,
            'status' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Quiz Test');

        $this->assertDatabaseHas('quizzes', [
            'lesson_id' => $lesson->id,
            'title' => 'Quiz Test',
        ]);
    }

    public function test_admin_can_update_quiz()
    {
        $lesson = Lesson::factory()->create();
        $quiz = Quiz::factory()->create(['lesson_id' => $lesson->id]);

        $response = $this->patchJson("/api/v1/admin/quizzes/{$quiz->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title');
    }

    public function test_admin_can_delete_quiz()
    {
        $lesson = Lesson::factory()->create();
        $quiz = Quiz::factory()->create(['lesson_id' => $lesson->id]);

        $response = $this->deleteJson("/api/v1/admin/quizzes/{$quiz->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('quizzes', ['id' => $quiz->id]);
    }

    public function test_admin_cannot_create_quiz_with_invalid_lesson()
    {
        $response = $this->postJson('/api/v1/admin/quizzes', [
            'lesson_id' => 999,
            'title' => 'Quiz Test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lesson_id']);
    }
}

<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Course\Models\Course;
use Modules\Lessons\Models\Lesson;
use Modules\Quiz\Models\Quiz;
use Modules\Teachers\Models\Teachers;
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

    private function createLesson(): Lesson
    {
        $teacher = Teachers::create(['name' => 'Teacher', 'slug' => 'teacher']);
        $course = Course::create([
            'name' => 'Course',
            'slug' => 'course-'.uniqid(),
            'teacher_id' => $teacher->id,
            'price' => 0,
            'level' => 'beginner',
        ]);

        return Lesson::create([
            'title' => 'Lesson',
            'slug' => 'lesson-'.uniqid(),
            'course_id' => $course->id,
            'type' => 'text',
            'order' => 0,
        ]);
    }

    public function test_admin_can_list_quizzes(): void
    {
        $lesson = $this->createLesson();
        Quiz::create(['lesson_id' => $lesson->id, 'title' => 'Quiz 1', 'max_attempts' => 3, 'status' => 1]);

        $response = $this->getJson('/api/v1/admin/quizzes');

        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_admin_can_create_quiz(): void
    {
        $lesson = $this->createLesson();

        $response = $this->postJson('/api/v1/admin/quizzes', [
            'lesson_id' => $lesson->id,
            'title' => 'Quiz Test',
            'max_attempts' => 3,
            'status' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Quiz Test');

        $this->assertDatabaseHas('quizzes', ['lesson_id' => $lesson->id, 'title' => 'Quiz Test']);
    }

    public function test_admin_can_update_quiz(): void
    {
        $lesson = $this->createLesson();
        $quiz = Quiz::create(['lesson_id' => $lesson->id, 'title' => 'Old Title', 'max_attempts' => 3, 'status' => 1]);

        $response = $this->patchJson("/api/v1/admin/quizzes/{$quiz->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(200)->assertJsonPath('data.title', 'Updated Title');
    }

    public function test_admin_can_delete_quiz(): void
    {
        $lesson = $this->createLesson();
        $quiz = Quiz::create(['lesson_id' => $lesson->id, 'title' => 'Quiz', 'max_attempts' => 3, 'status' => 1]);

        $response = $this->deleteJson("/api/v1/admin/quizzes/{$quiz->id}");

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertSoftDeleted('quizzes', ['id' => $quiz->id]);
    }

    public function test_admin_cannot_create_quiz_with_invalid_lesson(): void
    {
        $response = $this->postJson('/api/v1/admin/quizzes', [
            'lesson_id' => 999,
            'title' => 'Quiz Test',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['lesson_id']);
    }
}

<?php

namespace Tests\Feature\Student;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Lessons\Models\Lesson;
use Modules\Quiz\Models\Quiz;
use Modules\Quiz\Models\QuizQuestion;
use Modules\Students\Models\Student;
use Tests\TestCase;

class QuizTest extends TestCase
{
    use RefreshDatabase;

    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->student = Student::forceCreate([
            'name' => 'Student Test',
            'email' => 'student@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $this->actingAs($this->student, 'api');
    }

    public function test_student_can_get_quiz_without_correct_answers()
    {
        $lesson = Lesson::factory()->create();
        $quiz = Quiz::factory()->create(['lesson_id' => $lesson->id, 'status' => 1]);
        QuizQuestion::factory()->count(3)->create(['quiz_id' => $quiz->id]);

        $response = $this->getJson("/api/v1/lessons/{$lesson->id}/quiz");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.quiz.id', $quiz->id)
            ->assertJsonPath('data.questions.0.question', fn ($q) => strlen($q) > 0);

        // Verify correct_option is NOT in response
        $response->assertJsonMissing(['correct_option' => 'A']);
    }

    public function test_student_can_submit_quiz()
    {
        $lesson = Lesson::factory()->create();
        $quiz = Quiz::factory()->create(['lesson_id' => $lesson->id, 'status' => 1, 'max_attempts' => 3]);

        $questions = QuizQuestion::factory()->count(3)->create([
            'quiz_id' => $quiz->id,
            'correct_option' => 'A',
        ]);

        $answers = $questions->reduce(function ($carry, $q) {
            $carry[$q->id] = 'A';

            return $carry;
        }, []);

        $response = $this->postJson("/api/v1/quizzes/{$quiz->id}/submit", [
            'answers' => $answers,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.score', 3)
            ->assertJsonPath('data.total_questions', 3);

        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'score' => 3,
        ]);
    }

    public function test_student_cannot_exceed_max_attempts()
    {
        $lesson = Lesson::factory()->create();
        $quiz = Quiz::factory()->create(['lesson_id' => $lesson->id, 'max_attempts' => 1]);
        QuizQuestion::factory()->count(2)->create(['quiz_id' => $quiz->id]);

        // First attempt
        $this->postJson("/api/v1/quizzes/{$quiz->id}/submit", [
            'answers' => [1 => 'A', 2 => 'B'],
        ])->assertStatus(201);

        // Second attempt should fail
        $response = $this->postJson("/api/v1/quizzes/{$quiz->id}/submit", [
            'answers' => [1 => 'A', 2 => 'B'],
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_student_can_view_attempt_history()
    {
        $lesson = Lesson::factory()->create();
        $quiz = Quiz::factory()->create(['lesson_id' => $lesson->id]);

        // Create 2 attempts
        $quiz->attempts()->create([
            'student_id' => $this->student->id,
            'score' => 2,
            'total_questions' => 5,
            'answers' => ['1' => 'A', '2' => 'B'],
            'completed_at' => now(),
        ]);

        $quiz->attempts()->create([
            'student_id' => $this->student->id,
            'score' => 3,
            'total_questions' => 5,
            'answers' => ['1' => 'A', '2' => 'A'],
            'completed_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/quizzes/{$quiz->id}/attempts");

        $response->assertStatus(200)
            ->assertJsonPath('data.0.score', 3) // Latest first
            ->assertJsonPath('data.1.score', 2)
            ->assertJsonPath('pagination.total', 2);
    }
}

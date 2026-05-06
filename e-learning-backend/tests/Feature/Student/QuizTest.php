<?php

namespace Tests\Feature\Student;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Course\Models\Course;
use Modules\Lessons\Models\Lesson;
use Modules\Quiz\Models\Quiz;
use Modules\Quiz\Models\QuizAttempt;
use Modules\Quiz\Models\QuizQuestion;
use Modules\Students\Models\Student;
use Modules\Teachers\Models\Teachers;
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

    private function createLesson(): Lesson
    {
        $teacher = Teachers::create(['name' => 'Teacher', 'slug' => 'teacher-'.uniqid()]);
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

    private function createQuizWithQuestions(Lesson $lesson, int $questionCount = 3, string $correctOption = 'A'): Quiz
    {
        $quiz = Quiz::create([
            'lesson_id' => $lesson->id,
            'title' => 'Quiz Test',
            'max_attempts' => 3,
            'status' => 1,
        ]);

        for ($i = 0; $i < $questionCount; $i++) {
            QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question' => "Question $i",
                'option_a' => 'Option A',
                'option_b' => 'Option B',
                'option_c' => 'Option C',
                'option_d' => 'Option D',
                'correct_option' => $correctOption,
                'order' => $i,
            ]);
        }

        return $quiz;
    }

    public function test_student_can_get_quiz_without_correct_answers(): void
    {
        $lesson = $this->createLesson();
        $quiz = $this->createQuizWithQuestions($lesson);

        $response = $this->getJson("/api/v1/lessons/{$lesson->id}/quiz");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.quiz.id', $quiz->id);

        // Verify correct_option is NOT in response
        $content = $response->json('data.questions');
        $this->assertArrayNotHasKey('correct_option', $content[0]);
    }

    public function test_student_can_submit_quiz(): void
    {
        $lesson = $this->createLesson();
        $quiz = $this->createQuizWithQuestions($lesson, 3, 'A');

        $questions = $quiz->questions;
        $answers = $questions->mapWithKeys(fn ($q) => [$q->id => 'A'])->toArray();

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

    public function test_student_cannot_exceed_max_attempts(): void
    {
        $lesson = $this->createLesson();
        $quiz = Quiz::create([
            'lesson_id' => $lesson->id,
            'title' => 'Quiz',
            'max_attempts' => 1,
            'status' => 1,
        ]);
        QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question' => 'Q1',
            'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
            'correct_option' => 'A',
            'order' => 0,
        ]);

        // First attempt — success
        $questions = $quiz->questions;
        $answers = $questions->mapWithKeys(fn ($q) => [$q->id => 'A'])->toArray();
        $this->postJson("/api/v1/quizzes/{$quiz->id}/submit", ['answers' => $answers])
            ->assertStatus(201);

        // Second attempt — blocked
        $response = $this->postJson("/api/v1/quizzes/{$quiz->id}/submit", ['answers' => $answers]);
        $response->assertStatus(403)->assertJsonPath('success', false);
    }

    public function test_student_can_view_attempt_history(): void
    {
        $lesson = $this->createLesson();
        $quiz = Quiz::create([
            'lesson_id' => $lesson->id,
            'title' => 'Quiz',
            'max_attempts' => 5,
            'status' => 1,
        ]);

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'score' => 2,
            'total_questions' => 5,
            'answers' => ['1' => 'A'],
            'completed_at' => now()->subMinutes(5),
        ]);
        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'score' => 3,
            'total_questions' => 5,
            'answers' => ['1' => 'A'],
            'completed_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/quizzes/{$quiz->id}/attempts");

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertCount(2, $response->json('data'));
    }
}

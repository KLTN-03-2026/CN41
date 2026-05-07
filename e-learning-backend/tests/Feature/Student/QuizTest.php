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

    // ── Kết quả submit: correct_answers và questions phải có ────────────────

    public function test_submit_response_contains_correct_answers(): void
    {
        $lesson = $this->createLesson();
        $quiz = $this->createQuizWithQuestions($lesson, 3, 'B');
        $questions = $quiz->questions;
        $answers = $questions->mapWithKeys(fn ($q) => [$q->id => 'A'])->toArray(); // tất cả sai

        $response = $this->postJson("/api/v1/quizzes/{$quiz->id}/submit", ['answers' => $answers]);

        $response->assertStatus(201);

        $data = $response->json('data');

        // correct_answers phải là object, không null
        $this->assertNotNull($data['correct_answers'], 'correct_answers phải có trong response submit');
        $this->assertIsArray($data['correct_answers']);

        // Mỗi question_id phải map về đáp án đúng là 'B'
        foreach ($questions as $q) {
            $this->assertEquals('B', $data['correct_answers'][(string) $q->id],
                "correct_answers[{$q->id}] phải là 'B'");
        }
    }

    public function test_submit_response_contains_questions(): void
    {
        $lesson = $this->createLesson();
        $quiz = $this->createQuizWithQuestions($lesson, 2, 'C');
        $questions = $quiz->questions;
        $answers = $questions->mapWithKeys(fn ($q) => [$q->id => 'A'])->toArray();

        $response = $this->postJson("/api/v1/quizzes/{$quiz->id}/submit", ['answers' => $answers]);

        $response->assertStatus(201);
        $data = $response->json('data');

        // questions phải có trong response (để FE hiển thị nội dung câu hỏi)
        $this->assertNotNull($data['questions'], 'questions phải có trong response submit');
        $this->assertCount(2, $data['questions']);

        // Mỗi question phải có option_a..d nhưng KHÔNG có correct_option
        $q = $data['questions'][0];
        $this->assertArrayHasKey('option_a', $q);
        $this->assertArrayHasKey('option_b', $q);
        $this->assertArrayHasKey('option_c', $q);
        $this->assertArrayHasKey('option_d', $q);
        $this->assertArrayNotHasKey('correct_option', $q);
    }

    public function test_submit_all_correct_score_is_full(): void
    {
        $lesson = $this->createLesson();
        $quiz = $this->createQuizWithQuestions($lesson, 4, 'C');
        $questions = $quiz->questions;
        $answers = $questions->mapWithKeys(fn ($q) => [$q->id => 'C'])->toArray(); // tất cả đúng

        $response = $this->postJson("/api/v1/quizzes/{$quiz->id}/submit", ['answers' => $answers]);

        $response->assertStatus(201)
            ->assertJsonPath('data.score', 4)
            ->assertJsonPath('data.total_questions', 4)
            ->assertJsonPath('data.percentage', 100);
    }

    public function test_submit_all_wrong_score_is_zero(): void
    {
        $lesson = $this->createLesson();
        $quiz = $this->createQuizWithQuestions($lesson, 4, 'A');
        $questions = $quiz->questions;
        $answers = $questions->mapWithKeys(fn ($q) => [$q->id => 'B'])->toArray(); // tất cả sai

        $response = $this->postJson("/api/v1/quizzes/{$quiz->id}/submit", ['answers' => $answers]);

        $response->assertStatus(201)
            ->assertJsonPath('data.score', 0)
            ->assertJsonPath('data.percentage', 0);

        // correct_answers vẫn phải có dù điểm 0
        $this->assertNotNull($response->json('data.correct_answers'));
    }

    public function test_submit_mixed_answers_score_is_partial(): void
    {
        $lesson = $this->createLesson();
        $quiz = Quiz::create([
            'lesson_id' => $lesson->id,
            'title' => 'Mixed Quiz',
            'max_attempts' => 5,
            'status' => 1,
        ]);

        // Tạo 4 câu với đáp án đúng khác nhau
        $q1 = QuizQuestion::create(['quiz_id' => $quiz->id, 'question' => 'Q1', 'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D', 'correct_option' => 'A', 'order' => 0]);
        $q2 = QuizQuestion::create(['quiz_id' => $quiz->id, 'question' => 'Q2', 'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D', 'correct_option' => 'B', 'order' => 1]);
        $q3 = QuizQuestion::create(['quiz_id' => $quiz->id, 'question' => 'Q3', 'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D', 'correct_option' => 'C', 'order' => 2]);
        $q4 = QuizQuestion::create(['quiz_id' => $quiz->id, 'question' => 'Q4', 'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D', 'correct_option' => 'D', 'order' => 3]);

        // Học viên chọn đúng q1, q2 — sai q3, q4
        $response = $this->postJson("/api/v1/quizzes/{$quiz->id}/submit", [
            'answers' => [
                $q1->id => 'A', // đúng
                $q2->id => 'B', // đúng
                $q3->id => 'A', // sai (đúng là C)
                $q4->id => 'A', // sai (đúng là D)
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.score', 2)
            ->assertJsonPath('data.total_questions', 4)
            ->assertJsonPath('data.percentage', 50);

        $data = $response->json('data');

        // Verify correct_answers chính xác
        $this->assertEquals('A', $data['correct_answers'][(string) $q1->id]);
        $this->assertEquals('B', $data['correct_answers'][(string) $q2->id]);
        $this->assertEquals('C', $data['correct_answers'][(string) $q3->id]);
        $this->assertEquals('D', $data['correct_answers'][(string) $q4->id]);

        // Verify answers học viên được lưu — normalise key vì PHP encode integer-key array
        // thành numeric JSON array khi key liên tiếp
        $answersRaw = $data['answers'];
        $answersNorm = [];
        foreach ($answersRaw as $k => $v) {
            $answersNorm[(string) $k] = $v;
        }
        $this->assertContains('A', $answersNorm, 'Phải có ít nhất 1 câu học viên chọn A');
        // Đảm bảo số câu trả lời đúng với số câu gửi lên
        $this->assertCount(4, $answersNorm);
    }

    // ── Lịch sử: correct_answers và questions phải có ───────────────────────

    public function test_attempts_history_contains_correct_answers_and_questions(): void
    {
        $lesson = $this->createLesson();
        $quiz = $this->createQuizWithQuestions($lesson, 3, 'B');
        $questions = $quiz->questions;
        $answers = $questions->mapWithKeys(fn ($q) => [$q->id => 'A'])->toArray();

        // Submit trước
        $this->postJson("/api/v1/quizzes/{$quiz->id}/submit", ['answers' => $answers])
            ->assertStatus(201);

        // Lấy lịch sử
        $response = $this->getJson("/api/v1/quizzes/{$quiz->id}/attempts");
        $response->assertStatus(200);

        $attempt = $response->json('data.0');

        $this->assertNotNull($attempt['correct_answers'], 'correct_answers phải có trong history');
        $this->assertNotNull($attempt['questions'], 'questions phải có trong history');
        $this->assertCount(3, $attempt['questions']);

        // Verify từng câu có đáp án đúng là B
        foreach ($questions as $q) {
            $this->assertEquals('B', $attempt['correct_answers'][(string) $q->id]);
        }
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

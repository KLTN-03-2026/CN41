<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Course\Models\Course;
use Modules\Lessons\Models\Lesson;
use Modules\Quiz\Models\Quiz;
use Modules\Quiz\Models\QuizQuestion;
use Modules\Teachers\Models\Teachers;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class TeacherQuizPortalTest extends TestCase
{
    use HasAdminUser, RefreshDatabase;

    private User $teacher;

    private Teachers $teacherProfile;

    private User $otherTeacher;

    private Teachers $otherTeacherProfile;

    protected function setUp(): void
    {
        parent::setUp();

        // Đảm bảo role teacher tồn tại cho guard admin
        $role = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'admin']);

        // Tạo teacher 1
        $this->teacher = User::forceCreate([
            'name' => 'Teacher One',
            'email' => 'teacher_one@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $this->teacher->assignRole($role);
        $this->teacherProfile = Teachers::create([
            'user_id' => $this->teacher->id,
            'name' => 'Teacher One',
            'email' => 'teacher_one@test.com',
            'slug' => 'teacher-one-'.$this->teacher->id,
        ]);

        // Tạo teacher 2 (other teacher)
        $this->otherTeacher = User::forceCreate([
            'name' => 'Teacher Two',
            'email' => 'teacher_two@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $this->otherTeacher->assignRole($role);
        $this->otherTeacherProfile = Teachers::create([
            'user_id' => $this->otherTeacher->id,
            'name' => 'Teacher Two',
            'email' => 'teacher_two@test.com',
            'slug' => 'teacher-two-'.$this->otherTeacher->id,
        ]);
    }

    private function createCourseAndLesson(Teachers $teacher): array
    {
        $course = Course::create([
            'name' => 'Course for '.$teacher->name,
            'slug' => 'course-'.uniqid(),
            'teacher_id' => $teacher->id,
            'price' => 0,
            'level' => 'beginner',
        ]);

        $lesson = Lesson::create([
            'title' => 'Lesson '.uniqid(),
            'slug' => 'lesson-'.uniqid(),
            'course_id' => $course->id,
            'type' => 'quiz',
            'order' => 0,
        ]);

        $quiz = Quiz::create([
            'lesson_id' => $lesson->id,
            'title' => 'Quiz for '.$lesson->title,
            'max_attempts' => 3,
            'status' => 1,
        ]);

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question' => 'What is PHP?',
            'option_a' => 'Programming language',
            'option_b' => 'Coffee brand',
            'option_c' => 'Car model',
            'option_d' => 'Planet',
            'correct_option' => 'A',
            'order' => 1,
        ]);

        return [$course, $lesson, $quiz, $question];
    }

    public function test_teacher_can_view_own_lesson_quiz(): void
    {
        $this->actingAs($this->teacher, 'admin');

        [$course, $lesson, $quiz, $question] = $this->createCourseAndLesson($this->teacherProfile);

        $response = $this->getJson("/api/v1/teacher/lesson-quiz/{$lesson->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.quiz.id', $quiz->id);
    }

    public function test_teacher_cannot_view_other_teachers_lesson_quiz(): void
    {
        // Login as Teacher One
        $this->actingAs($this->teacher, 'admin');

        // Tạo course/lesson của Teacher Two
        [$course, $lesson, $quiz, $question] = $this->createCourseAndLesson($this->otherTeacherProfile);

        // Teacher One gọi xem quiz của lesson thuộc Teacher Two -> phải lỗi 404
        $response = $this->getJson("/api/v1/teacher/lesson-quiz/{$lesson->id}");

        $response->assertStatus(404);
    }

    public function test_teacher_can_update_own_quiz_question(): void
    {
        $this->actingAs($this->teacher, 'admin');

        [$course, $lesson, $quiz, $question] = $this->createCourseAndLesson($this->teacherProfile);

        $response = $this->patchJson("/api/v1/teacher/quiz-questions/{$question->id}", [
            'question' => 'Updated question text',
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseHas('quiz_questions', [
            'id' => $question->id,
            'question' => 'Updated question text',
        ]);
    }

    public function test_teacher_cannot_update_other_teachers_quiz_question(): void
    {
        // Login as Teacher One
        $this->actingAs($this->teacher, 'admin');

        // Tạo course/lesson/question của Teacher Two
        [$course, $lesson, $quiz, $question] = $this->createCourseAndLesson($this->otherTeacherProfile);

        // Teacher One cố cập nhật question của Teacher Two -> 404
        $response = $this->patchJson("/api/v1/teacher/quiz-questions/{$question->id}", [
            'question' => 'Hack attempt',
        ]);

        $response->assertStatus(404);
        $this->assertDatabaseHas('quiz_questions', [
            'id' => $question->id,
            'question' => 'What is PHP?', // vẫn giữ nguyên
        ]);
    }

    public function test_teacher_can_delete_own_quiz_question(): void
    {
        $this->actingAs($this->teacher, 'admin');

        [$course, $lesson, $quiz, $question] = $this->createCourseAndLesson($this->teacherProfile);

        $response = $this->deleteJson("/api/v1/teacher/quiz-questions/{$question->id}");

        $response->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseMissing('quiz_questions', [
            'id' => $question->id,
        ]);
    }

    public function test_teacher_cannot_delete_other_teachers_quiz_question(): void
    {
        // Login as Teacher One
        $this->actingAs($this->teacher, 'admin');

        // Tạo course/lesson/question của Teacher Two
        [$course, $lesson, $quiz, $question] = $this->createCourseAndLesson($this->otherTeacherProfile);

        // Teacher One cố xóa question của Teacher Two -> 404
        $response = $this->deleteJson("/api/v1/teacher/quiz-questions/{$question->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('quiz_questions', [
            'id' => $question->id, // vẫn tồn tại
        ]);
    }
}

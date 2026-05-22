<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Course\Models\Course;
use Modules\Teachers\Models\Teachers;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherCoursePortalTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Teachers $teacherProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::forceCreate([
            'name' => 'Teacher Test',
            'email' => 'teacher_test@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $role = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'admin']);
        $this->teacher->assignRole($role);
        $this->actingAs($this->teacher, 'admin');

        $this->teacherProfile = Teachers::create([
            'user_id' => $this->teacher->id,
            'name' => 'Teacher Test',
            'slug' => 'teacher-test-'.$this->teacher->id,
            'exp' => 0,
            'status' => 1,
        ]);
    }

    public function test_teacher_can_create_course(): void
    {
        $response = $this->postJson('/api/v1/teacher/courses', [
            'name' => 'Laravel 12 Course',
            'slug' => 'laravel-12-course-test',
            'price' => 0,
            'level' => 'beginner',
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);

        $this->assertDatabaseHas('courses', [
            'slug' => 'laravel-12-course-test',
            'teacher_id' => $this->teacherProfile->id,
        ]);
    }

    public function test_teacher_cannot_see_other_teachers_course(): void
    {
        $otherTeacherProfile = Teachers::create([
            'user_id' => null,
            'name' => 'Other Teacher',
            'slug' => 'other-teacher-'.uniqid(),
            'exp' => 0,
            'status' => 1,
        ]);

        $course = Course::create([
            'name' => 'Other Course',
            'slug' => 'other-course-'.uniqid(),
            'teacher_id' => $otherTeacherProfile->id,
            'price' => 0,
            'level' => 'beginner',
        ]);

        $response = $this->getJson("/api/v1/teacher/courses/{$course->id}");
        $response->assertStatus(404);
    }

    public function test_teacher_can_create_section_in_own_course(): void
    {
        $course = Course::create([
            'name' => 'My Course',
            'slug' => 'my-course-section-test',
            'teacher_id' => $this->teacherProfile->id,
            'price' => 0,
            'level' => 'beginner',
        ]);

        $response = $this->postJson("/api/v1/teacher/courses/{$course->id}/sections", [
            'title' => 'Chapter 1',
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);
        $this->assertDatabaseHas('sections', [
            'title' => 'Chapter 1',
            'course_id' => $course->id,
        ]);
    }

    public function test_teacher_can_create_lesson_in_own_course(): void
    {
        $course = Course::create([
            'name' => 'My Course Lesson',
            'slug' => 'my-course-lesson-test',
            'teacher_id' => $this->teacherProfile->id,
            'price' => 0,
            'level' => 'beginner',
        ]);

        $response = $this->postJson("/api/v1/teacher/courses/{$course->id}/lessons", [
            'title' => 'Lesson 1',
            'type' => 'text',
            'content' => 'Some text content',
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);
        $this->assertDatabaseHas('lessons', [
            'course_id' => $course->id,
            'title' => 'Lesson 1',
        ]);
    }
}

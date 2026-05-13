<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Course\Models\Course;
use Modules\Lessons\Models\Lesson;
use Modules\Lessons\Models\Section;
use Modules\Teachers\Models\Teachers;
use Modules\Upload\Models\MediaFile;
use Tests\TestCase;

class SectionLessonTest extends TestCase
{
    use RefreshDatabase, \Tests\Traits\HasAdminUser;

    private string $baseUrl = '/api/v1/admin';

    private function createCourse()
    {
        $teacher = Teachers::create([
            'name' => 'Teacher Test',
            'slug' => 'teacher-test',
        ]);

        return Course::create([
            'name' => 'Course Test',
            'slug' => 'course-test',
            'teacher_id' => $teacher->id,
            'price' => 100000,
            'level' => 'beginner',
        ]);
    }

    // ── Sections Tests ──

    public function test_sections_index_returns_success()
    {
        $this->setupAdmin();
        $course = $this->createCourse();

        $response = $this->getJson($this->baseUrl."/courses/{$course->id}/sections");

        $response->assertStatus(200);
    }

    public function test_create_section_success()
    {
        $this->setupAdmin();
        $course = $this->createCourse();

        $response = $this->postJson($this->baseUrl."/courses/{$course->id}/sections", [
            'title' => 'Chương 1: Giới thiệu',
            'status' => 1,
            'order' => 0,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('sections', [
            'title' => 'Chương 1: Giới thiệu',
            'course_id' => $course->id,
        ]);
    }

    public function test_update_section_success()
    {
        $this->setupAdmin();
        $course = $this->createCourse();
        $section = Section::create(['title' => 'Old Title', 'course_id' => $course->id, 'order' => 0]);

        $response = $this->patchJson($this->baseUrl."/sections/{$section->id}", [
            'title' => 'New Title',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'title' => 'New Title',
        ]);
    }

    public function test_delete_section_success()
    {
        $this->setupAdmin();
        $course = $this->createCourse();
        $section = Section::create(['title' => 'To Delete', 'course_id' => $course->id, 'order' => 0]);

        $response = $this->deleteJson($this->baseUrl."/sections/{$section->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('sections', ['id' => $section->id]);
    }

    public function test_reorder_sections_success()
    {
        $this->setupAdmin();
        $course = $this->createCourse();
        $s1 = Section::create(['title' => 'S1', 'course_id' => $course->id, 'order' => 0]);
        $s2 = Section::create(['title' => 'S2', 'course_id' => $course->id, 'order' => 1]);

        $response = $this->postJson($this->baseUrl.'/sections/reorder', [
            'orders' => [
                ['id' => $s1->id, 'order' => 1],
                ['id' => $s2->id, 'order' => 0],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('sections', ['id' => $s1->id, 'order' => 1]);
        $this->assertDatabaseHas('sections', ['id' => $s2->id, 'order' => 0]);
    }

    public function test_delete_section_keeps_lessons_as_orphans()
    {
        $this->setupAdmin();
        $course = $this->createCourse();
        $section = Section::create(['title' => 'S1', 'course_id' => $course->id, 'order' => 0]);
        $lesson = Lesson::create([
            'title' => 'L1',
            'course_id' => $course->id,
            'section_id' => $section->id,
            'type' => 'text',
            'slug' => 'l1',
            'order' => 0,
        ]);

        $response = $this->deleteJson($this->baseUrl."/sections/{$section->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('sections', ['id' => $section->id]);

        // Bài giảng vẫn còn nhưng section_id phải là null
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'section_id' => null,
        ]);
    }

    public function test_toggle_section_status()
    {
        $this->setupAdmin();
        $course = $this->createCourse();
        $section = Section::create(['title' => 'S1', 'course_id' => $course->id, 'order' => 0, 'status' => 0]);

        $response = $this->patchJson($this->baseUrl."/sections/{$section->id}/toggle-status");

        $response->assertStatus(200);
        $this->assertDatabaseHas('sections', ['id' => $section->id, 'status' => 1]);
    }

    // ── Lessons Tests ──

    public function test_lessons_index_returns_success()
    {
        $this->setupAdmin();
        $course = $this->createCourse();

        $response = $this->getJson($this->baseUrl."/courses/{$course->id}/lessons");

        $response->assertStatus(200);
    }

    public function test_create_lesson_success()
    {
        $this->setupAdmin();
        $course = $this->createCourse();
        $section = Section::create(['title' => 'S1', 'course_id' => $course->id, 'order' => 0]);

        $video = MediaFile::create([
            'original_name' => 'test.mp4',
            'path' => 'tests/test.mp4',
            'url' => '/storage/tests/test.mp4',
            'type' => 'video',
            'disk' => 'public',
            'mime_type' => 'video/mp4',
            'size' => 1024,
        ]);

        $response = $this->postJson($this->baseUrl."/courses/{$course->id}/lessons", [
            'title' => 'Bài giảng 1',
            'type' => 'video',
            'video_id' => $video->id,
            'section_id' => $section->id,
            'status' => 1,
            'order' => 0,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('lessons', [
            'title' => 'Bài giảng 1',
            'section_id' => $section->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_create_lesson_without_section()
    {
        $this->setupAdmin();
        $course = $this->createCourse();

        $response = $this->postJson($this->baseUrl."/courses/{$course->id}/lessons", [
            'title' => 'Bài không chương',
            'type' => 'text',
            'content' => 'Nội dung bài học text',
            'section_id' => null,
            'order' => 0,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('lessons', [
            'title' => 'Bài không chương',
            'section_id' => null,
        ]);
    }

    public function test_update_lesson_success()
    {
        $this->setupAdmin();
        $course = $this->createCourse();
        $lesson = Lesson::create([
            'title' => 'Old Title',
            'course_id' => $course->id,
            'type' => 'text',
            'slug' => 'old-title',
            'order' => 0,
        ]);

        $response = $this->patchJson($this->baseUrl."/lessons/{$lesson->id}", [
            'title' => 'New Title',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'title' => 'New Title',
        ]);
    }

    public function test_toggle_lesson_status()
    {
        $this->setupAdmin();
        $course = $this->createCourse();
        $lesson = Lesson::create([
            'title' => 'L1',
            'course_id' => $course->id,
            'type' => 'text',
            'slug' => 'l1',
            'status' => 0,
            'order' => 0,
        ]);

        $response = $this->patchJson($this->baseUrl."/lessons/{$lesson->id}/toggle-status");

        $response->assertStatus(200);
        $this->assertDatabaseHas('lessons', ['id' => $lesson->id, 'status' => 1]);
    }

    public function test_reorder_lessons_success()
    {
        $this->setupAdmin();
        $course = $this->createCourse();
        $l1 = Lesson::create(['title' => 'L1', 'course_id' => $course->id, 'type' => 'text', 'slug' => 'l1', 'order' => 0]);
        $l2 = Lesson::create(['title' => 'L2', 'course_id' => $course->id, 'type' => 'text', 'slug' => 'l2', 'order' => 1]);

        $response = $this->postJson($this->baseUrl.'/lessons/reorder', [
            'orders' => [
                ['id' => $l1->id, 'order' => 1],
                ['id' => $l2->id, 'order' => 0],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('lessons', ['id' => $l1->id, 'order' => 1]);
        $this->assertDatabaseHas('lessons', ['id' => $l2->id, 'order' => 0]);
    }

    public function test_bulk_assign_lessons_to_section()
    {
        $this->setupAdmin();
        $course = $this->createCourse();
        $section = Section::create(['title' => 'S1', 'course_id' => $course->id, 'order' => 0]);
        $l1 = Lesson::create(['title' => 'L1', 'course_id' => $course->id, 'type' => 'text', 'slug' => 'l1', 'order' => 0]);
        $l2 = Lesson::create(['title' => 'L2', 'course_id' => $course->id, 'type' => 'text', 'slug' => 'l2', 'order' => 1]);

        $response = $this->postJson($this->baseUrl.'/lessons/bulk-action', [
            'ids' => [$l1->id, $l2->id],
            'action' => 'assign-section',
            'section_id' => $section->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('lessons', ['id' => $l1->id, 'section_id' => $section->id]);
        $this->assertDatabaseHas('lessons', ['id' => $l2->id, 'section_id' => $section->id]);
    }

    public function test_bulk_unassign_lessons_from_section()
    {
        $this->setupAdmin();
        $course = $this->createCourse();
        $section = Section::create(['title' => 'S1', 'course_id' => $course->id, 'order' => 0]);
        $l1 = Lesson::create(['title' => 'L1', 'course_id' => $course->id, 'section_id' => $section->id, 'type' => 'text', 'slug' => 'l1', 'order' => 0]);

        $response = $this->postJson($this->baseUrl.'/lessons/bulk-action', [
            'ids' => [$l1->id],
            'action' => 'assign-section',
            'section_id' => null, // Bỏ gán
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('lessons', ['id' => $l1->id, 'section_id' => null]);
    }

    public function test_delete_lesson_success()
    {
        $this->setupAdmin();
        $course = $this->createCourse();
        $lesson = Lesson::create([
            'title' => 'To Delete',
            'course_id' => $course->id,
            'type' => 'text',
            'slug' => 'to-delete',
            'order' => 0,
        ]);

        $response = $this->deleteJson($this->baseUrl."/lessons/{$lesson->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('lessons', ['id' => $lesson->id]);
    }

    public function test_create_lesson_fails_without_required_fields()
    {
        $this->setupAdmin();
        $course = $this->createCourse();

        $response = $this->postJson($this->baseUrl."/courses/{$course->id}/lessons", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'type']);
    }

    public function test_lesson_trashed_returns_deleted_lessons()
    {
        $this->setupAdmin();
        $course = $this->createCourse();
        $lesson = Lesson::create([
            'title' => 'Trashed Lesson',
            'course_id' => $course->id,
            'type' => 'text',
            'slug' => 'trashed-lesson',
            'order' => 0,
        ]);
        $lesson->delete();

        $response = $this->getJson($this->baseUrl.'/lessons/trashed');

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Trashed Lesson']);
    }

    public function test_lesson_restore_success()
    {
        $this->setupAdmin();
        $course = $this->createCourse();
        $lesson = Lesson::create([
            'title' => 'Restore Lesson',
            'course_id' => $course->id,
            'type' => 'text',
            'slug' => 'restore-lesson',
            'order' => 0,
        ]);
        $lesson->delete();

        $response = $this->patchJson($this->baseUrl."/lessons/{$lesson->id}/restore");

        $response->assertStatus(200);
        $this->assertDatabaseHas('lessons', ['id' => $lesson->id, 'deleted_at' => null]);
    }

    public function test_lesson_force_delete_success()
    {
        $this->setupAdmin();
        $course = $this->createCourse();
        $lesson = Lesson::create([
            'title' => 'Force Delete Lesson',
            'course_id' => $course->id,
            'type' => 'text',
            'slug' => 'force-delete-lesson',
            'order' => 0,
        ]);
        $lesson->delete();

        $response = $this->deleteJson($this->baseUrl."/lessons/{$lesson->id}/force-delete");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
    }

    public function test_bulk_delete_lessons_success()
    {
        $this->setupAdmin();
        $course = $this->createCourse();
        $l1 = Lesson::create(['title' => 'BL1', 'course_id' => $course->id, 'type' => 'text', 'slug' => 'bl1', 'order' => 0]);
        $l2 = Lesson::create(['title' => 'BL2', 'course_id' => $course->id, 'type' => 'text', 'slug' => 'bl2', 'order' => 1]);

        $response = $this->deleteJson($this->baseUrl.'/lessons/bulk-delete', [
            'ids' => [$l1->id, $l2->id],
        ]);

        $response->assertStatus(200);
        $this->assertSoftDeleted('lessons', ['id' => $l1->id]);
        $this->assertSoftDeleted('lessons', ['id' => $l2->id]);
    }

    public function test_bulk_restore_lessons_success()
    {
        $this->setupAdmin();
        $course = $this->createCourse();
        $l1 = Lesson::create(['title' => 'BRL1', 'course_id' => $course->id, 'type' => 'text', 'slug' => 'brl1', 'order' => 0]);
        $l2 = Lesson::create(['title' => 'BRL2', 'course_id' => $course->id, 'type' => 'text', 'slug' => 'brl2', 'order' => 1]);
        $l1->delete();
        $l2->delete();

        $response = $this->patchJson($this->baseUrl.'/lessons/bulk-restore', [
            'ids' => [$l1->id, $l2->id],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('lessons', ['id' => $l1->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('lessons', ['id' => $l2->id, 'deleted_at' => null]);
    }

    public function test_section_restore_success()
    {
        $this->setupAdmin();
        $course = $this->createCourse();
        $section = Section::create(['title' => 'Restore Section', 'course_id' => $course->id, 'order' => 0]);
        $section->delete();

        $response = $this->patchJson($this->baseUrl."/sections/{$section->id}/restore");

        $response->assertStatus(200);
        $this->assertDatabaseHas('sections', ['id' => $section->id, 'deleted_at' => null]);
    }

    public function test_section_force_delete_success()
    {
        $this->setupAdmin();
        $course = $this->createCourse();
        $section = Section::create(['title' => 'Force Section', 'course_id' => $course->id, 'order' => 0]);
        $section->delete();

        $response = $this->deleteJson($this->baseUrl."/sections/{$section->id}/force-delete");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
    }
}

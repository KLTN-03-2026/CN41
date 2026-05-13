<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Students\Models\Student;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase, \Tests\Traits\HasAdminUser;

    private string $baseUrl = '/api/v1/admin/students';

    public function test_students_index_returns_success()
    {
        $this->setupAdmin();
        for ($i = 0; $i < 3; $i++) {
            Student::create(['name' => "S $i", 'email' => "s$i@test.com", 'password' => 'password']);
        }

        $response = $this->getJson($this->baseUrl);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_students_index_requires_admin()
    {
        $response = $this->getJson($this->baseUrl);
        $response->assertStatus(401);
    }

    public function test_create_student_success()
    {
        $this->setupAdmin();

        $response = $this->postJson($this->baseUrl, [
            'name' => 'Student Test',
            'email' => 'student_test@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'date_of_birth' => '2000-01-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('students', [
            'email' => 'student_test@gmail.com',
            'name' => 'Student Test',
        ]);
    }

    public function test_create_student_fails_without_required_fields()
    {
        $this->setupAdmin();

        $response = $this->postJson($this->baseUrl, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_create_student_fails_duplicate_email()
    {
        $this->setupAdmin();
        Student::create([
            'name' => 'Existing',
            'email' => 'duplicate@test.com',
            'password' => 'password',
        ]);

        $response = $this->postJson($this->baseUrl, [
            'name' => 'New',
            'email' => 'duplicate@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_show_student_returns_success()
    {
        $this->setupAdmin();
        $student = Student::create([
            'name' => 'Show Test',
            'email' => 'show@test.com',
            'password' => 'password',
        ]);

        $response = $this->getJson($this->baseUrl.'/'.$student->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Show Test')
            ->assertJsonStructure(['data' => ['enrolled_courses', 'orders_count', 'total_spent']]);
    }

    public function test_update_student_success()
    {
        $this->setupAdmin();
        $student = Student::create([
            'name' => 'Old Name',
            'email' => 'old@test.com',
            'password' => 'password',
        ]);

        $response = $this->patchJson($this->baseUrl.'/'.$student->id, [
            'name' => 'New Name',
            'email' => 'old@test.com',
            'date_of_birth' => '1995-05-05',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name' => 'New Name',
        ]);
    }

    public function test_delete_student_soft_delete()
    {
        $this->setupAdmin();
        $student = Student::create([
            'name' => 'Delete Me',
            'email' => 'delete@test.com',
            'password' => 'password',
        ]);

        $response = $this->deleteJson($this->baseUrl.'/'.$student->id);

        $response->assertStatus(200);
        $this->assertSoftDeleted('students', ['id' => $student->id]);
    }

    public function test_trashed_returns_deleted_students()
    {
        $this->setupAdmin();
        $student = Student::create(['name' => 'Trashed', 'email' => 'trashed@test.com', 'password' => 'password']);
        $student->delete();

        $response = $this->getJson($this->baseUrl.'/trashed');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Trashed']);
    }

    public function test_restore_student_success()
    {
        $this->setupAdmin();
        $student = Student::create(['name' => 'Restored', 'email' => 'restored@test.com', 'password' => 'password']);
        $student->delete();

        $response = $this->patchJson($this->baseUrl.'/'.$student->id.'/restore');

        $response->assertStatus(200);
        $this->assertDatabaseHas('students', ['id' => $student->id, 'deleted_at' => null]);
    }

    public function test_force_delete_student_success()
    {
        $this->setupAdmin();
        $student = Student::create(['name' => 'Force', 'email' => 'force@test.com', 'password' => 'password']);
        $student->delete();

        $response = $this->deleteJson($this->baseUrl.'/'.$student->id.'/force-delete');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    public function test_bulk_delete_students_success()
    {
        $this->setupAdmin();
        $s1 = Student::create(['name' => 'S1', 'email' => 's1@test.com', 'password' => 'password']);
        $s2 = Student::create(['name' => 'S2', 'email' => 's2@test.com', 'password' => 'password']);

        $response = $this->deleteJson($this->baseUrl.'/bulk-delete', [
            'ids' => [$s1->id, $s2->id],
        ]);

        $response->assertStatus(200);
        $this->assertSoftDeleted('students', ['id' => $s1->id]);
        $this->assertSoftDeleted('students', ['id' => $s2->id]);
    }

    public function test_bulk_restore_students_success()
    {
        $this->setupAdmin();
        $s1 = Student::create(['name' => 'S1', 'email' => 's1@test.com', 'password' => 'password']);
        $s2 = Student::create(['name' => 'S2', 'email' => 's2@test.com', 'password' => 'password']);
        $s1->delete();
        $s2->delete();

        $response = $this->patchJson($this->baseUrl.'/bulk-restore', [
            'ids' => [$s1->id, $s2->id],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('students', ['id' => $s1->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('students', ['id' => $s2->id, 'deleted_at' => null]);
    }

    public function test_bulk_force_delete_students_success()
    {
        $this->setupAdmin();
        $s1 = Student::create(['name' => 'S1', 'email' => 's1@test.com', 'password' => 'password']);
        $s1->delete();

        $response = $this->deleteJson($this->baseUrl.'/bulk-force-delete', [
            'ids' => [$s1->id],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('students', ['id' => $s1->id]);
    }

    public function test_students_search()
    {
        $this->setupAdmin();
        Student::create(['name' => 'John Doe', 'email' => 'john@test.com', 'password' => 'password']);
        Student::create(['name' => 'Jane Smith', 'email' => 'jane@test.com', 'password' => 'password']);

        $response = $this->getJson($this->baseUrl.'?search=John');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'John Doe'])
            ->assertJsonMissing(['name' => 'Jane Smith']);
    }

    public function test_students_pagination()
    {
        $this->setupAdmin();
        for ($i = 0; $i < 20; $i++) {
            Student::create(['name' => "S $i", 'email' => "s$i@test.com", 'password' => 'password']);
        }

        $response = $this->getJson($this->baseUrl.'?per_page=10');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.per_page', 10)
            ->assertJsonPath('pagination.total', 20);
    }
}

<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Teachers\Models\Teachers;
use Tests\TestCase;

class TeacherTest extends TestCase
{
    use RefreshDatabase, \Tests\Traits\HasAdminUser;

    private string $baseUrl = '/api/v1/admin/teachers';

    public function test_teachers_index_returns_success()
    {
        $this->setupAdmin();
        Teachers::create(['name' => 'Teacher 1', 'slug' => 'teacher-1']);
        Teachers::create(['name' => 'Teacher 2', 'slug' => 'teacher-2']);

        $response = $this->getJson($this->baseUrl);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_teachers_index_requires_admin()
    {
        $response = $this->getJson($this->baseUrl);
        $response->assertStatus(401);
    }

    public function test_create_teacher_success()
    {
        $this->setupAdmin();

        $response = $this->postJson($this->baseUrl, [
            'name' => 'New Teacher',
            'slug' => 'new-teacher',
            'description' => 'Test Bio',
            'exp' => 5,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('teachers', [
            'name' => 'New Teacher',
            'slug' => 'new-teacher',
        ]);
    }

    public function test_create_teacher_fails_without_required_fields()
    {
        $this->setupAdmin();

        $response = $this->postJson($this->baseUrl, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'slug']);
    }

    public function test_update_teacher_success()
    {
        $this->setupAdmin();
        $teacher = Teachers::create(['name' => 'Old Teacher', 'slug' => 'old-teacher']);

        $response = $this->putJson($this->baseUrl.'/'.$teacher->id, [
            'name' => 'Updated Teacher',
            'slug' => 'updated-teacher',
            'description' => 'Updated Bio',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'name' => 'Updated Teacher',
            'slug' => 'updated-teacher',
        ]);
    }

    public function test_toggle_teacher_status()
    {
        $this->setupAdmin();
        $teacher = Teachers::create(['name' => 'T1', 'slug' => 't1', 'status' => 1]);

        $response = $this->patchJson($this->baseUrl.'/'.$teacher->id.'/toggle-status');

        $response->assertStatus(200);
        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'status' => 0]);

        $this->patchJson($this->baseUrl.'/'.$teacher->id.'/toggle-status');
        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'status' => 1]);
    }

    public function test_delete_teacher_soft_delete()
    {
        $this->setupAdmin();
        $teacher = Teachers::create(['name' => 'T1', 'slug' => 't1']);

        $response = $this->deleteJson($this->baseUrl.'/'.$teacher->id);

        $response->assertStatus(200);
        $this->assertSoftDeleted('teachers', ['id' => $teacher->id]);
    }

    public function test_trashed_returns_deleted_teachers()
    {
        $this->setupAdmin();
        $teacher = Teachers::create(['name' => 'Trashed', 'slug' => 'trashed']);
        $teacher->delete();

        $response = $this->getJson($this->baseUrl.'/trashed');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Trashed']);
    }

    public function test_restore_teacher_success()
    {
        $this->setupAdmin();
        $teacher = Teachers::create(['name' => 'Restored', 'slug' => 'restored']);
        $teacher->delete();

        $response = $this->postJson($this->baseUrl.'/'.$teacher->id.'/restore');

        $response->assertStatus(200);
        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'deleted_at' => null]);
    }

    public function test_force_delete_teacher_success()
    {
        $this->setupAdmin();
        $teacher = Teachers::create(['name' => 'Force', 'slug' => 'force']);
        $teacher->delete();

        $response = $this->deleteJson($this->baseUrl.'/'.$teacher->id.'/force-delete');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('teachers', ['id' => $teacher->id]);
    }

    public function test_bulk_delete_teachers_success()
    {
        $this->setupAdmin();
        $t1 = Teachers::create(['name' => 'T1', 'slug' => 't1']);
        $t2 = Teachers::create(['name' => 'T2', 'slug' => 't2']);

        $response = $this->deleteJson($this->baseUrl.'/bulk-delete', [
            'ids' => [$t1->id, $t2->id],
        ]);

        $response->assertStatus(200);
        $this->assertSoftDeleted('teachers', ['id' => $t1->id]);
        $this->assertSoftDeleted('teachers', ['id' => $t2->id]);
    }

    public function test_bulk_restore_teachers_success()
    {
        $this->setupAdmin();
        $t1 = Teachers::create(['name' => 'T1', 'slug' => 't1']);
        $t2 = Teachers::create(['name' => 'T2', 'slug' => 't2']);
        $t1->delete();
        $t2->delete();

        $response = $this->postJson($this->baseUrl.'/bulk-restore', [
            'ids' => [$t1->id, $t2->id],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('teachers', ['id' => $t1->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('teachers', ['id' => $t2->id, 'deleted_at' => null]);
    }

    public function test_teachers_search_and_filter()
    {
        $this->setupAdmin();
        Teachers::create(['name' => 'Alice', 'slug' => 'alice', 'status' => 1]);
        Teachers::create(['name' => 'Bob', 'slug' => 'bob', 'status' => 0]);

        // Search
        $response = $this->getJson($this->baseUrl.'?search=Alice');
        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Alice'])
            ->assertJsonMissing(['name' => 'Bob']);

        // Filter Status
        $response = $this->getJson($this->baseUrl.'?status=0');
        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Bob'])
            ->assertJsonMissing(['name' => 'Alice']);
    }

    public function test_public_teachers_list_only_active()
    {
        Teachers::create(['name' => 'Active', 'slug' => 'active', 'status' => 1]);
        Teachers::create(['name' => 'Inactive', 'slug' => 'inactive', 'status' => 0]);

        $response = $this->getJson('/api/v1/teachers');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Active'])
            ->assertJsonMissing(['name' => 'Inactive']);
    }
}

<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Users\Database\Seeders\RolePermissionSeeder;
use Modules\Users\Models\User;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class UserTest extends TestCase
{
    use HasAdminUser, RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = $this->setupAdmin();
    }

    public function test_index_returns_paginated_users_through_resource(): void
    {
        User::forceCreate([
            'name' => 'Alice',
            'email' => 'alice@test.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->getJson('/api/v1/admin/users');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'status', 'roles'],
                ],
                'pagination',
            ]);

        $this->assertArrayNotHasKey('password', $response->json('data.0'));
        $this->assertArrayNotHasKey('remember_token', $response->json('data.0'));
    }

    public function test_bulk_delete_blocks_self_deletion(): void
    {
        $response = $this->deleteJson('/api/v1/admin/users/bulk-delete', [
            'ids' => [$this->admin->id],
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $this->admin->id, 'deleted_at' => null]);
    }

    public function test_bulk_delete_blocks_super_admin_deletion(): void
    {
        $superAdmin = User::forceCreate([
            'name' => 'Super2',
            'email' => 'super2@test.com',
            'password' => bcrypt('password'),
        ]);
        $superAdmin->assignRole('super-admin');

        $response = $this->deleteJson('/api/v1/admin/users/bulk-delete', [
            'ids' => [$superAdmin->id],
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'deleted_at' => null]);
    }
}

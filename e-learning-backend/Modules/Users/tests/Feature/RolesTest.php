<?php

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Users\Database\Seeders\RolePermissionSeeder;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Chạy seeder để có sẵn quyền và vai trò mặc định
        $this->seed(RolePermissionSeeder::class);

        // Tạo một Super Admin để test
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
    }

    /**
     * Test admin can list all roles.
     */
    public function test_admin_can_list_roles(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/v1/admin/roles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'guard_name',
                        'permissions',
                        'users_count',
                    ],
                ],
            ]);

        $this->assertGreaterThan(0, count($response->json('data')));
    }

    /**
     * Test admin can get all permissions.
     */
    public function test_admin_can_get_all_permissions(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/v1/admin/permissions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'guard_name'],
                ],
            ]);
    }

    /**
     * Test admin can create a new role with permissions.
     */
    public function test_admin_can_create_role_with_permissions(): void
    {
        $permissions = ['users.view', 'users.create'];

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/v1/admin/roles', [
                'name' => 'editor',
                'permissions' => $permissions,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'editor');

        $this->assertDatabaseHas('roles', [
            'name' => 'editor',
            'guard_name' => 'admin',
        ]);

        $role = Role::findByName('editor', 'admin');
        $this->assertTrue($role->hasAllPermissions($permissions));
    }

    /**
     * Test admin can update role and sync permissions.
     */
    public function test_admin_can_update_role_permissions(): void
    {
        $role = Role::create(['name' => 'staff', 'guard_name' => 'admin']);
        $role->syncPermissions(['users.view']);

        $response = $this->actingAs($this->admin, 'admin')
            ->patchJson("/api/v1/admin/roles/{$role->id}", [
                'name' => 'staff-updated',
                'permissions' => ['users.view', 'users.edit'],
            ]);

        $response->assertStatus(200);

        $role->refresh();
        $this->assertEquals('staff-updated', $role->name);
        $this->assertTrue($role->hasPermissionTo('users.edit'));
    }

    /**
     * Test cannot update super-admin role.
     */
    public function test_cannot_update_super_admin_role(): void
    {
        $superAdminRole = Role::findByName('super-admin', 'admin');

        $response = $this->actingAs($this->admin, 'admin')
            ->patchJson("/api/v1/admin/roles/{$superAdminRole->id}", [
                'name' => 'hacker-admin',
            ]);

        $response->assertStatus(403);
        $this->assertEquals('super-admin', $superAdminRole->refresh()->name);
    }

    /**
     * Test cannot delete super-admin role.
     */
    public function test_cannot_delete_super_admin_role(): void
    {
        $superAdminRole = Role::findByName('super-admin', 'admin');

        $response = $this->actingAs($this->admin, 'admin')
            ->deleteJson("/api/v1/admin/roles/{$superAdminRole->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('roles', ['id' => $superAdminRole->id]);
    }

    /**
     * Test cannot delete role assigned to users.
     */
    public function test_cannot_delete_role_assigned_to_users(): void
    {
        $role = Role::create(['name' => 'manager', 'guard_name' => 'admin']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($this->admin, 'admin')
            ->deleteJson("/api/v1/admin/roles/{$role->id}");

        $response->assertStatus(400);
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    /**
     * Test admin can delete an unassigned role.
     */
    public function test_admin_can_delete_unassigned_role(): void
    {
        $role = Role::create(['name' => 'temporary', 'guard_name' => 'admin']);

        $response = $this->actingAs($this->admin, 'admin')
            ->deleteJson("/api/v1/admin/roles/{$role->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }
}

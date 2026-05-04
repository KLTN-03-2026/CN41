<?php

namespace Tests\Traits;

use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;

trait HasAdminUser
{
    protected function setupAdmin(string $email = 'admin_test@test.com')
    {
        $admin = User::forceCreate([
            'name' => 'Admin Test',
            'email' => $email,
            'password' => 'password123',
        ]);

        $role = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'admin',
        ]);
        $admin->assignRole($role);

        $this->actingAs($admin, 'admin');

        return $admin;
    }
}

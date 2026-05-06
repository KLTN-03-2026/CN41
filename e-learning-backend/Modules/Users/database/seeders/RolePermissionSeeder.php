<?php

namespace Modules\Users\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'admin';

        // ── Permissions ──
        $permissions = [
            // Users & Roles
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            // Courses
            'courses.view', 'courses.create', 'courses.edit', 'courses.delete',
            // Categories
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            // Lessons
            'lessons.view', 'lessons.create', 'lessons.edit', 'lessons.delete',
            // Quizzes
            'quizzes.view', 'quizzes.create', 'quizzes.edit', 'quizzes.delete',
            // Orders & Coupons
            'orders.view', 'orders.edit',
            'coupons.view', 'coupons.create', 'coupons.edit', 'coupons.delete',
            // Students
            'students.view', 'students.edit',
            // Posts (News)
            'posts.view', 'posts.create', 'posts.edit', 'posts.delete',
            'tags.view', 'tags.create', 'tags.edit', 'tags.delete',
            'comments.view', 'comments.delete',
            // Dashboard
            'dashboard.view',
            // System Logs
            'system.logs.view', 'system.logs.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guard]);
        }

        // ── Roles ──
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => $guard]);
        $admin = Role::firstOrCreate(['name' => 'admin',       'guard_name' => $guard]);
        $teacher = Role::firstOrCreate(['name' => 'teacher',     'guard_name' => $guard]);

        // super-admin có tất cả permissions
        $superAdmin->syncPermissions(Permission::where('guard_name', $guard)->get());

        // admin có tất cả trừ users.delete
        $admin->syncPermissions(
            Permission::where('guard_name', $guard)
                ->where('name', '!=', 'users.delete')
                ->get()
        );

        // teacher chỉ quản lý courses & lessons của mình
        $teacher->syncPermissions([
            'courses.view', 'courses.create', 'courses.edit', 'courses.delete',
            'lessons.view', 'lessons.create', 'lessons.edit', 'lessons.delete',
            'quizzes.view', 'quizzes.create', 'quizzes.edit',
            'dashboard.view',
            'categories.view',
            'users.view',
            'tags.view',
            'students.view',
            'comments.view',
            'comments.delete',
            'posts.view',
            'orders.view',
        ]);
    }
}

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
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'admin';

        // Delete renamed permissions so they don't linger in the DB
        Permission::where('guard_name', $guard)
            ->whereIn('name', [
                'users.view', 'users.create', 'users.edit', 'users.delete',
                'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            ])
            ->delete();

        $permissions = [
            // Admin-user management (staff accounts — /admin/users)
            'admin_users.view', 'admin_users.create', 'admin_users.edit', 'admin_users.delete',
            // Roles & permissions management (/admin/roles)
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            // Teacher account management (/admin/teachers)
            'teachers.view', 'teachers.create', 'teachers.edit', 'teachers.delete',
            // Courses
            'courses.view', 'courses.create', 'courses.edit', 'courses.delete',
            // Course categories (/admin/categories)
            'course_categories.view', 'course_categories.create', 'course_categories.edit', 'course_categories.delete',
            // Post categories (/admin/post-categories)
            'post_categories.view', 'post_categories.create', 'post_categories.edit', 'post_categories.delete',
            // Lessons
            'lessons.view', 'lessons.create', 'lessons.edit', 'lessons.delete',
            // Quizzes
            'quizzes.view', 'quizzes.create', 'quizzes.edit', 'quizzes.delete',
            // Orders & Coupons
            'orders.view', 'orders.edit', 'orders.export',
            'coupons.view', 'coupons.create', 'coupons.edit', 'coupons.delete',
            // Students
            'students.view', 'students.edit',
            // Posts / News
            'posts.view', 'posts.create', 'posts.edit', 'posts.delete',
            'tags.view', 'tags.create', 'tags.edit', 'tags.delete',
            'comments.view', 'comments.delete',
            // Commission module
            'payouts.view', 'payouts.approve', 'payouts.export',
            'teacher_earnings.view', 'teacher_earnings.export',
            'commission_settings.view', 'commission_settings.update',
            // Dashboard
            'dashboard.view',
            // System logs
            'system.logs.view', 'system.logs.delete',
            // Feature flags (super-admin only — assigned via Gate::before)
            'feature_flags.view', 'feature_flags.update',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guard]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => $guard]);
        $admin = Role::firstOrCreate(['name' => 'admin',       'guard_name' => $guard]);
        $teacher = Role::firstOrCreate(['name' => 'teacher',     'guard_name' => $guard]);

        // super-admin gets all permissions
        $superAdmin->syncPermissions(Permission::where('guard_name', $guard)->get());

        // admin gets all except admin_users.delete (cannot delete other admins)
        $admin->syncPermissions(
            Permission::where('guard_name', $guard)
                ->where('name', '!=', 'admin_users.delete')
                ->get()
        );

        // teacher manages only their own courses/lessons (portal at /teacher/*)
        $teacher->syncPermissions([
            'courses.view', 'courses.create', 'courses.edit', 'courses.delete',
            'lessons.view', 'lessons.create', 'lessons.edit', 'lessons.delete',
            'quizzes.view', 'quizzes.create', 'quizzes.edit',
            'dashboard.view',
            'course_categories.view',
            'teacher_earnings.export',
        ]);
    }
}

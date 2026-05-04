<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;

$roleName = 'teacher';
$guard = 'admin';

$role = Role::where('name', $roleName)->where('guard_name', $guard)->first();

if (! $role) {
    echo "ERROR: Role '{$roleName}' for guard '{$guard}' not found!\n";
    $allRoles = Role::all(['name', 'guard_name']);
    echo "Available roles:\n";
    foreach ($allRoles as $r) {
        echo "- {$r->name} ({$r->guard_name})\n";
    }
    exit;
}

echo "Role: {$role->name} | Guard: {$role->guard_name}\n";
echo 'Permissions count: '.$role->permissions->count()."\n";
echo "Permissions list:\n";
foreach ($role->permissions as $p) {
    echo "- {$p->name} ({$p->guard_name})\n";
}

echo "\n--- SAMPLE TEACHER CHECK ---\n";
$teacherUser = User::role($roleName, $guard)->first();
if ($teacherUser) {
    echo "Found user: {$teacherUser->email}\n";
    echo 'User roles: '.implode(', ', $teacherUser->getRoleNames()->toArray())."\n";
    echo "Can 'courses.view'? ".($teacherUser->can('courses.view') ? 'YES' : 'NO')."\n";
    echo "Can 'lessons.view'? ".($teacherUser->can('lessons.view') ? 'YES' : 'NO')."\n";
} else {
    echo "No users found with role '{$roleName}'\n";
}

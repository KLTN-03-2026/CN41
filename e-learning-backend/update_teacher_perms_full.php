<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

echo "Updating teacher permissions to full operational set...\n";

$role = Role::where('name', 'teacher')->where('guard_name', 'admin')->first();

if ($role) {
    $fullPermissions = [
        'courses.view', 'courses.create', 'courses.edit', 'courses.delete',
        'lessons.view', 'lessons.create', 'lessons.edit', 'lessons.delete',
        'dashboard.view',
        'categories.view',
        'users.view',
        'tags.view',
        'students.view',
        'comments.view',
        'comments.delete',
        'posts.view',
        'orders.view',
    ];

    foreach ($fullPermissions as $permName) {
        Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'admin']);
    }

    $role->syncPermissions($fullPermissions);
    echo "Successfully updated 'teacher' role with ".count($fullPermissions)." permissions.\n";
} else {
    echo "Role 'teacher' not found.\n";
}

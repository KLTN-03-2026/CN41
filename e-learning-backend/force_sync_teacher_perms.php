<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

echo "Force syncing ALL necessary permissions for Teacher role...\n";

// Reset cache ngay lập tức
app()[PermissionRegistrar::class]->forgetCachedPermissions();

$role = Role::where('name', 'teacher')->where('guard_name', 'admin')->first();

if ($role) {
    $fullPermissions = [
        // Courses (Full)
        'courses.view', 'courses.create', 'courses.edit', 'courses.delete',
        // Lessons & Sections (Full)
        'lessons.view', 'lessons.create', 'lessons.edit', 'lessons.delete',
        // Auxiliary for Forms
        'categories.view',
        'tags.view',
        // Others
        'dashboard.view',
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

    // Clear cache lần nữa sau khi sync
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    echo 'Successfully force-synced '.count($fullPermissions)." permissions to 'teacher'.\n";
    echo 'Permissions: '.implode(', ', $fullPermissions)."\n";
} else {
    echo "Role 'teacher' not found.\n";
}

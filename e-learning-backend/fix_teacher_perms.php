<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

echo "Updating teacher permissions...\n";

$role = Role::where('name', 'teacher')->where('guard_name', 'admin')->first();

if ($role) {
    $neededPermissions = [
        'categories.view',
        'users.view',
    ];

    foreach ($neededPermissions as $permName) {
        $perm = Permission::where('name', $permName)->where('guard_name', 'admin')->first();
        if (! $perm) {
            echo "Creating missing permission: {$permName}...\n";
            Permission::create(['name' => $permName, 'guard_name' => 'admin']);
        }
    }

    $role->givePermissionTo($neededPermissions);
    echo "Successfully added permissions to 'teacher' role.\n";
} else {
    echo "Role 'teacher' (admin) not found.\n";
}

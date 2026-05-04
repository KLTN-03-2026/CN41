<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Spatie\Permission\Models\Role;

echo "Adjusting teacher permissions to hide Admin/Teacher menus...\n";

$role = Role::where('name', 'teacher')->where('guard_name', 'admin')->first();

if ($role) {
    $role->revokePermissionTo('users.view');
    echo "Successfully revoked 'users.view' from 'teacher' role.\n";
} else {
    echo "Role 'teacher' not found.\n";
}

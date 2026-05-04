<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

echo "Restoring users.view to teacher and clearing cache...\n";

$role = Role::where('name', 'teacher')->where('guard_name', 'admin')->first();

if ($role) {
    $role->givePermissionTo('users.view');
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    echo "Done.\n";
}

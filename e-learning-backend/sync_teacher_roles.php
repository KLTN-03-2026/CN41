<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Modules\Teachers\Models\Teachers;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;

echo "Synchronizing teacher roles...\n";

// Ensure role exists for admin guard
$role = Role::where('name', 'teacher')->where('guard_name', 'admin')->first();
if (! $role) {
    echo "Creating 'teacher' role for 'admin' guard...\n";
    $role = Role::create(['name' => 'teacher', 'guard_name' => 'admin']);
}

$teachers = Teachers::all();
$count = 0;
$skipped = 0;

foreach ($teachers as $t) {
    $user = User::find($t->user_id);
    if ($user) {
        $user->assignRole($role);
        $count++;
    } else {
        $skipped++;
    }
}

echo "Done!\n";
echo "Assigned 'teacher' role to {$count} users.\n";
echo "Skipped {$skipped} teachers (no linked user found).\n";

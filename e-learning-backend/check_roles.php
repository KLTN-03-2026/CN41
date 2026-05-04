<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;

echo "--- ROLES LIST ---\n";
Role::all()->each(function ($role) {
    echo "ID: {$role->id} | Name: {$role->name} | Guard: {$role->guard_name}\n";
});

echo "\n--- USER COUNTS PER ROLE (model_has_roles table) ---\n";
$counts = DB::table('model_has_roles')
    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
    ->select('roles.name', 'roles.guard_name', DB::raw('count(*) as user_count'))
    ->groupBy('roles.name', 'roles.guard_name')
    ->get();

foreach ($counts as $row) {
    echo "Role: {$row->name} | Guard: {$row->guard_name} | Users: {$row->user_count}\n";
}

echo "\n--- TEACHER USERS CHECK ---\n";
$teachers = User::role('teacher')->get();
echo 'Found '.$teachers->count()." users with role 'teacher' via Spatie method.\n";

if ($teachers->count() > 0) {
    echo 'Sample teacher: '.$teachers->first()->email."\n";
}

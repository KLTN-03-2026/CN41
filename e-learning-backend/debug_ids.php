<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

echo "--- TEACHERS TABLE ---\n";
$teachers = DB::table('teachers')->select('id', 'user_id', 'name')->get();
foreach ($teachers as $t) {
    echo "Teacher ID: {$t->id} | UserID (foreign key): {$t->user_id} | Name: {$t->name}\n";
}

echo "\n--- USERS TABLE ---\n";
$users = DB::table('users')->select('id', 'name', 'email')->get();
foreach ($users as $u) {
    echo "User ID: {$u->id} | Name: {$u->name} | Email: {$u->email}\n";
}

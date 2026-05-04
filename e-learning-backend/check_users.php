<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Modules\Students\Models\Student;
use Modules\Users\Models\User;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$user = User::where('email', 'superadmin@elearning.com')->first();
if ($user) {
    if (Hash::check('password', $user->password)) {
        echo 'Admin Login Success! Name: '.$user->name."\n";
        try {
            $token = $user->createToken('admin-token')->plainTextToken;
            echo 'Token generated: '.substr($token, 0, 10)."...\n";
        } catch (Exception $e) {
            echo 'Token generation failed: '.$e->getMessage()."\n";
        }
    } else {
        echo "Admin Login Failed: Incorrect password.\n";
    }
} else {
    echo "Admin User not found.\n";
}

$student = Student::where('email', 'mai.nguyen@gmail.com')->first();
if ($student) {
    if (Hash::check('password', $student->password)) {
        echo 'Student Login Success! Name: '.$student->name."\n";
        try {
            $token = $student->createToken('student-token')->plainTextToken;
            echo 'Token generated: '.substr($token, 0, 10)."...\n";
        } catch (Exception $e) {
            echo 'Token generation failed: '.$e->getMessage()."\n";
        }
    } else {
        echo "Student Login Failed: Incorrect password.\n";
    }
} else {
    echo "Student User not found.\n";
}

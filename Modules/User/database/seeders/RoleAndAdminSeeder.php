<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleAndAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Tạo các Role dành riêng cho hệ thống Admin (thay thế cho bảng groups/modules)
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Content Editor', 'guard_name' => 'web']);

        // 2. Tạo tài khoản Admin hệ thống (lưu vào bảng users)
        $admin = User::updateOrCreate(
        ['email' => 'admin@elearning.test'],
        [
            'email' => 'admin@elearning.test',
            'name' => 'System Admin',
            'password' => Hash::make('12345678'),
        ]
        );

        // 3. Gán Role Super Admin cho tài khoản hệ thống này
        $admin->assignRole($superAdminRole);
    }
}

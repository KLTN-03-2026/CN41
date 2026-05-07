<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite không hỗ trợ MODIFY ENUM — chỉ chạy trên MySQL/MariaDB
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE lessons MODIFY type ENUM('video', 'document', 'text', 'quiz') NOT NULL DEFAULT 'video'");
        }
        // SQLite: validation đã được thêm ở FormRequest, DB không enforce enum
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE lessons MODIFY type ENUM('video', 'document', 'text') NOT NULL DEFAULT 'video'");
        }
    }
};

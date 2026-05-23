<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            $table->string('hls_path')->nullable()->after('path');
            $table->string('hls_key', 32)->nullable()->after('hls_path');
            $table->enum('hls_status', ['pending', 'processing', 'ready', 'failed'])->nullable()->after('hls_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            $table->dropColumn(['hls_path', 'hls_key', 'hls_status']);
        });
    }
};

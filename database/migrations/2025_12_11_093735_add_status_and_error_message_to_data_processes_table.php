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
        Schema::table('data_processes', function (Blueprint $table) {
            $table->enum('status', ['success', 'error'])->default('success')->after('data');
            $table->text('error_message')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_processes', function (Blueprint $table) {
            $table->dropColumn(['status', 'error_message']);
        });
    }
};

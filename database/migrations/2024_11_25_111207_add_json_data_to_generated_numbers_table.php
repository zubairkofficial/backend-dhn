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
        Schema::table('generated_numbers', function (Blueprint $table) {
            $table->json('json_data')->nullable()->after('BM');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generated_numbers', function (Blueprint $table) {
            $table->dropColumn('json_data');
        });
    }
};

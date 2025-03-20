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
        Schema::create('free_data_processes', function (Blueprint $table) {
            $table->id();
            $table->string('file_name'); // Column for the file name
            $table->longText('data')->nullable();
            $table->unsignedBigInteger('user_id')->nullable(); // Add user_id column
            $table->timestamps();

            // Set foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('free_data_processes');
    }
};

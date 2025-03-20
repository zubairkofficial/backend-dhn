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
        Schema::create('contract_solutions', function (Blueprint $table) {
            $table->id();
            $table->string('doctype');
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
        Schema::table('contract_solutions', function (Blueprint $table) {
            $table->dropForeign(['user_id']); // Drop the foreign key constraint
            $table->dropColumn('user_id'); // Drop the user_id column
        });

        Schema::dropIfExists('contract_solutions');
    }
};

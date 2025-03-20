<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('download_logs', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id'); // Who downloaded
        $table->string('file_name'); // File downloaded
        $table->timestamp('downloaded_at')->useCurrent(); // Timestamp
        $table->timestamps();

        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('download_logs');
    }
};

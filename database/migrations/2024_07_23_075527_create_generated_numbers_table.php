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
        Schema::create('generated_numbers', function (Blueprint $table) {
            $table->id(); 
            $table->string('number')->nullable();
            $table->string('Datum')->nullable();
            $table->string('Thema')->nullable();
            $table->string('Teilnehmer')->nullable();
            $table->string('Niederlassungsleiter')->nullable();
            $table->string('auther')->nullable();
            $table->string('BM')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_numbers');
    }
};

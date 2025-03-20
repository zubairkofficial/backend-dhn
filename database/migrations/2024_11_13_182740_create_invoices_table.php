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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('category')->nullable();
            $table->string('currency_code', 10)->nullable();
            $table->date('date')->nullable();
            $table->string('due_date')->nullable();
            $table->string('document_type', 50)->nullable();
            $table->string('subtotal')->nullable();
            $table->string('tax')->nullable();
            $table->string('total')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('latest_added_time')->useCurrent();
            $table->string('quantity')->nullable();
            $table->string('price')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

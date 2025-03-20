<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('counter_limit')->nullable(); // Add counter limit column
            $table->integer('current_usage')->nullable(); // Add current usage column
            $table->date('expiration_date')->nullable(); // Add expiration date column
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('counter_limit'); // Drop counter limit column
            $table->dropColumn('current_usage'); // Drop current usage column
            $table->dropColumn('expiration_date'); // Drop expiration date column
        });
    }
}

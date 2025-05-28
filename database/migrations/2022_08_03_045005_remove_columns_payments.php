<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('customer_id');
            $table->dropColumn('appointment_id');
            $table->dropColumn('payment');
            $table->dropColumn('payable');
            $table->dropColumn('paid');
            $table->dropColumn('state');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->integer('customer_id');
            $table->integer('appointment_id');
            $table->string('payment');
            $table->string('payable');
            $table->string('paid');
            $table->string('state');
        });
    }
};

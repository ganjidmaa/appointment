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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id');
            $table->integer('user_id');
            $table->integer('appointment_id');
            $table->string('payment');
            $table->string('payable');
            $table->string('paid');
            $table->integer('coupon_code_id')->nullable();
            $table->string('coupon_amount')->nullable();
            $table->integer('discount_id')->nullable();
            $table->smallInteger('discount_percent')->nullable();
            $table->string('discount_amount')->nullable();
            $table->string('cash_amount')->nullable();
            $table->string('other_amount')->nullable();
            $table->string('state');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
};

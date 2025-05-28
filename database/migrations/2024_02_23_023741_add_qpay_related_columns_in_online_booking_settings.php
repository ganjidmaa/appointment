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
        Schema::table('online_booking_settings', function (Blueprint $table) {
            $table->boolean('choose_qpay')->default(0);
            $table->boolean('choose_autoDiscard')->default(0);
            $table->decimal('validate_amount', 13 , 0)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('online_booking_settings', function (Blueprint $table) {
            //
        });
    }
};

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
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('sms_send');
            $table->string('daily_sms_reminder_txt');
            $table->smallInteger('daily_sms_reminder_minutes');
            $table->string('monthly_sms_reminder_txt');
            $table->smallInteger('monthly_sms_reminder_months');
            $table->integer('sms_count');
            $table->integer('sms_limit');



        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            //
        });
    }
};

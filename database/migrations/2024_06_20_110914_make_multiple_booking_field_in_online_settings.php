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
        Schema::table('online_booking_settings', function (Blueprint $table) {
            $table->boolean('group_booking')->default(false);
            $table->smallInteger('group_booking_limit')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('online_booking_settings', function (Blueprint $table) {
            //
        });
    }
};

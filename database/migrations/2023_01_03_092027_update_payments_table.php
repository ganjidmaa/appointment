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
            $table->dropColumn('coupon_code_id');
            $table->dropColumn('coupon_amount');
            $table->dropColumn('discount_id');
            $table->dropColumn('discount_percent');
            $table->dropColumn('discount_amount');
            $table->dropColumn('cash_amount');
            $table->dropColumn('other_amount');
            $table->string('type')->after('user_id');
            $table->integer('amount')->after('type');
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
            //
        });
    }
};

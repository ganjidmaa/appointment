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
        Schema::table('branches', function (Blueprint $table) {
            $table->string('qpay_merchant_id')->nullable();
            $table->boolean('use_qpay')->default(false);
            $table->string('bank_code')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_holder')->nullable();
            $table->string('mcc_code')->nullable(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            //
        });
    }
};

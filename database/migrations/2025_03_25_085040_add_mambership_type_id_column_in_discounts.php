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
        Schema::table('invoice_discounts', function (Blueprint $table) {
            $table->integer('membership_type_id')->nullable()->after('discount_percent');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_discounts', function (Blueprint $table) {
            $table->dropColumn('membership_type_id');
        });
    }
};

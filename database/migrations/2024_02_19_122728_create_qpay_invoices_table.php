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
        Schema::create('qpay_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_id')->nullable();
            $table->string('branch')->nullable();
            $table->decimal('amount', 13, 0);
            $table->text('desc');
            $table->boolean('is_success')->default(false);
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
        Schema::dropIfExists('qpay_invoices');
    }
};

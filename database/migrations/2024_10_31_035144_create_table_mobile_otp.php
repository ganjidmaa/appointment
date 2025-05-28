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
        Schema::create('mobile_otp', function (Blueprint $table) {
            $table->id();
            $table->string(column: 'mobile');   
            $table->string(column: 'confirm_code');
            $table->smallInteger(column: 'counter');
            $table->boolean(column: 'status')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_otp');
    }
};

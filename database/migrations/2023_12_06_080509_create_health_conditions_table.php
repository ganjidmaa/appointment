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
        Schema::create('health_conditions', function (Blueprint $table) {
            $table->id();
            $table->integer('appointment_id');

            $table->json('farsightedness');
            $table->json('Ph');
            $table->json('with_glasses');
            $table->json('nearsightedness');

            $table->json('air_tonometer');
            $table->json('CCT');

            $table->json('go_scope');
            $table->json('eye_movement');
            $table->json('refraction');
            $table->json('cranial_angle');
            $table->json('color');
            $table->json('pathological_discharge');
            $table->json('tear_path');
            $table->json('eye_recesses');
            $table->json('eyelids');
            $table->json('mucus');
            $table->json('sclera');
            $table->json('cornea');
            $table->json('sought_camera');
            $table->json('rainbow_cover');
            $table->json('pupil');
            $table->json('RAPD');
            $table->json('crystal');
            $table->json('glass');
            $table->json('eye_disk');
            $table->json('CDR');
            $table->json('A_V');
            $table->json('S_H');
            $table->json('K_W');
            $table->json('S_S');
            $table->json('reticulated');
            $table->json('yallow_dot');
            $table->json('outside');
            $table->json('distance_R');
            $table->json('distance_L');
            $table->json('near_R');
            $table->json('near_L');
            
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
        Schema::dropIfExists('health_conditions');
    }
};

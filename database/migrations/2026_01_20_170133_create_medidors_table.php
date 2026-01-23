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
        Schema::create('medidores', function (Blueprint $table) {
            $table->id('id_medidor');
            $table->string('cod_medidor');
            $table->bigInteger('id_almacen');
            $table->string('eac_Tar_1');
            $table->string('eac_Tar_2');
            $table->string('eac_Total');
            $table->string('Max_demanda');
            $table->string('eric_Total');
            $table->string('volt_l1_neutro');
            $table->string('volt_l2_neutro');
            $table->string('volt_l3_neutro');
            $table->string('volt_l1l2');
            $table->string('volt_l2l3');
            $table->string('volt_l3l1');
            $table->string('corr_l1');
            $table->string('corr_l2');
            $table->string('corr_l3');
            $table->string('pont_act_l1');
            $table->string('pont_act_l2');
            $table->string('pont_act_l3');
            $table->string('pont_act_total');
            $table->string('ener_act_total');
            $table->bigInteger('user_created');
            $table->bigInteger('user_updated');
            $table->string('estado_medidor');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medidores');
    }
};

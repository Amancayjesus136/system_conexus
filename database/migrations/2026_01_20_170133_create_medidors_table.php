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

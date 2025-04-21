<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTipoGanadoVacunaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ganado_tipo_vacuna', function (Blueprint $table) {
            $table->id();
            $table->enum('sexo', ['H', 'M']);
            $table->foreignId('ganado_tipo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vacuna_id')->constrained()->cascadeOnDelete();
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
        Schema::dropIfExists('tipo_ganado_vacuna');
    }
}

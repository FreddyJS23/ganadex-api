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
        Schema::create('vacunas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->smallInteger('intervalo_dosis');
            $table->enum('tipo_vacuna', ['medica', 'plan_sanitario']);
            $table->integer('dosis_recomendada_anual')->nullable();
            $table->boolean('aplicable_a_todos')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacunas');
    }
};

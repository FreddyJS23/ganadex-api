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
        Schema::create('ganados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->smallInteger('numero')->unique()->nullable($value=true);
            $table->enum('sexo',['H','M']);
            $table->string('origen')->nullable($value=true);
            $table->date('fecha_nacimiento')->nullable($value=true);
            $table->foreignId('tipo_id')->constrained(table:'ganado_tipos',indexName:'ganado_tipo_id');
            $table->foreignId('finca_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ganados');
    }
};

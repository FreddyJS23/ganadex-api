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
        Schema::create('partos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('observacion');
            $table->foreignId('toro_id')->constrained();
            $table->foreignId('ganado_id')->constrained();
            $table->foreignId('ganado_cria_id')->constrained(table:'ganados',indexName:'ganado_cria_id')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partos');
    }
};

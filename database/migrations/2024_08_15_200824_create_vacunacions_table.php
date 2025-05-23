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
        Schema::create('vacunacions', function (Blueprint $table) {
            $table->id();
            $table->date('prox_dosis');
            $table->date('fecha');
            $table->foreignId('vacuna_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ganado_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hacienda_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacunacions');
    }
};

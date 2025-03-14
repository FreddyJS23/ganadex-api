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
        Schema::create('fallecimientos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('descripcion')->nullable();
            $table->foreignId('causas_fallecimiento_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ganado_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fallecimientos');
    }
};

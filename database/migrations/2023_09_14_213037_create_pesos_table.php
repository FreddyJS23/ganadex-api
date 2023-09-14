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
        Schema::create('pesos', function (Blueprint $table) {
            $table->id();
            $table->string('peso_nacimiento',10)->nullable($value=true);
            $table->string('peso_destete',10)->nullable($value=true);
            $table->string('peso_2year',10)->nullable($value=true);
            $table->string('peso_actual',10)->nullable($value=true);
            $table->foreignId('ganado_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesos');
    }
};

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
            $table->smallInteger('peso_nacimiento')->nullable($value = true);
            $table->smallInteger('peso_destete')->nullable($value = true);
            $table->smallInteger('peso_2year')->nullable($value = true);
            $table->smallInteger('peso_actual')->nullable($value = true);
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

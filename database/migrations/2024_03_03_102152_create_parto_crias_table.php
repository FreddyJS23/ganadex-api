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
        Schema::create('parto_crias', function (Blueprint $table) {
            $table->id();
            $table->string('observacion')->nullable();
            $table->foreignId('ganado_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parto_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parto_crias');
    }
};

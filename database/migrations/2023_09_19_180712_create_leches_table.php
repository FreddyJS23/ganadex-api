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
        Schema::create('leches', function (Blueprint $table) {
            $table->id();
            $table->decimal('peso_leche',4,2);
            $table->date('fecha');
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
        Schema::dropIfExists('leches');
    }
};

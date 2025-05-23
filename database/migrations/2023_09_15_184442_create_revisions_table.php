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
        Schema::create('revisions', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('tipo_revision_id');
            $table->string('diagnostico')->nullable();
            $table->string('tratamiento')->nullable();
            $table->foreignId('ganado_id')->constrained()->cascadeOnDelete();
            $table->foreignId('personal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vacuna_id')->nullable()->constrained()->cascadeOnDelete();
            $table->integer('dosis')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revisions');
    }
};

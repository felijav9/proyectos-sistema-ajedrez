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
        Schema::create('emparejamientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ronda_id')->constrained('rondas');
            $table->foreignId('blancas_id')->constrained('jugadores');
            $table->foreignId('negras_id')->constrained('jugadores');
            $table->string('resultado')->nullable(); // Ej: 1-0, 0-1, 0.5-0.5
            $table->integer('mesa');
            $table->integer('estacion'); // Para saber si es Estación 1, 2 o 3
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emparejamientos');
    }
};

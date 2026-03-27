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
        Schema::create('jugadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('cascade');
            $table->string('nombre');
            $table->char('tablero', 1)->nullable(); // A, B, C o D
            $table->integer('elo')->default(0);
            
            // --- NUEVOS CAMPOS ---
            $table->integer('edad')->nullable(); // Guardamos el número de años
            $table->string('genero')->nullable(); // Para 'Masculino', 'Femenino' o 'Otro'
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jugadores');
    }
};
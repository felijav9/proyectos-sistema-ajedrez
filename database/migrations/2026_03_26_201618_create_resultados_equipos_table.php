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
        Schema::create('resultados_equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ronda_id')
                ->nullable()
                ->constrained()
                ->onDelete('cascade');
            $table->foreignId('equipo_id')->constrained()->onDelete('cascade');

            $table->decimal('puntos_individuales', 4, 1)->default(0);
            $table->integer('puntos_globales')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resultados_equipos');
    }
};

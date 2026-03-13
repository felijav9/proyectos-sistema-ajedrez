<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Jugador;
use App\Models\Equipo;

class JugadorSeeder extends Seeder
{
    public function run(): void
    {
        $datos = [
            'Los Campeones' => [
                'A' => 'Daniel Solis',
                'B' => 'David Nolasco',
                'C' => 'Carla Blanco',
                'D' => 'Andrea Roblero',
            ],
            'Changos FC' => [
                'A' => 'Joaquin Mendez',
                'B' => 'Abner Utuy',
                'C' => 'Esteban Abril',
                'D' => 'Nahil Ortiz',
            ],
            'Gambitos' => [
                'A' => 'Juan Diego Pacheco',
                'B' => 'Mario',
                'C' => 'Joshua',
                'D' => 'Alejandra Abril',
            ],
            'Bloops' => [
                'A' => 'David Najera',
                'B' => 'Christopher Diaz',
                'C' => 'Ajbe Ortiz',
                'D' => 'Fernando Jolon',
            ],
            'Apertura Maestra' => [
                'A' => 'Steven Acevedo',
                'B' => 'Andres Gomez',
                'C' => 'Celeste Mendez',
                'D' => 'Mateo Roblero',
            ],
            'Gambito de Dama' => [
                'A' => 'Edgar Gonzalez',
                'B' => 'Carlos Esteban',
                'C' => 'Emiliano Pacheco',
                'D' => 'Saqmuj Aguilar',
            ],
        ];

        foreach ($datos as $nombreEquipo => $miembros) {
            // Buscamos el ID del equipo por su nombre
            $equipo = Equipo::where('nombre', $nombreEquipo)->first();

            if ($equipo) {
                foreach ($miembros as $letra => $nombreJugador) {
                    Jugador::firstOrCreate([
                        'equipo_id' => $equipo->id,
                        'tablero' => $letra,
                        'nombre' => $nombreJugador,
                    ]);
                }
            }
        }
    }
}

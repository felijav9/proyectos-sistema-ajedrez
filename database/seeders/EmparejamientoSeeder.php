<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Emparejamiento;
use App\Models\Jugador;
use App\Models\Ronda;

class EmparejamientoSeeder extends Seeder
{
    public function run(): void
    {
        // Helper para obtener ID de jugador
        $getJ = function ($nombre) {
            $jugador = Jugador::where('nombre', trim($nombre))->first();

            if (!$jugador) {
                $this->command->error("Jugador no encontrado: {$nombre}");
                return null;
            }

            return $jugador->id;
        };

        // Colores base por tablero
        $colores = [
            'A' => 'blancas',
            'B' => 'negras',
            'C' => 'blancas',
            'D' => 'negras'
        ];

        $tableros = ['A','B','C','D'];

        // ROUND ROBIN CORRECTO (6 equipos)
        $rondasEquipos = [

            1 => [
                ['Bloops','Gambito de Dama'],
                ['Changos FC','Gambitos'],
                ['Apertura Maestra','Los Campeones'],
            ],

            2 => [
                ['Bloops','Gambitos'],
                ['Los Campeones','Changos FC'],
                ['Gambito de Dama','Apertura Maestra'],
            ],

            3 => [
                ['Bloops','Los Campeones'],
                ['Gambitos','Apertura Maestra'],
                ['Gambito de Dama','Changos FC'],
            ],

            4 => [
                ['Bloops','Changos FC'],
                ['Los Campeones','Gambito de Dama'],
                ['Apertura Maestra','Gambitos'],
            ],

            5 => [
                ['Bloops','Apertura Maestra'],
                ['Changos FC','Gambito de Dama'],
                ['Gambitos','Los Campeones'],
            ],
        ];

        // Segunda vuelta (rondas 6-10)
        for ($i = 6; $i <= 10; $i++) {
            $rondasEquipos[$i] = $rondasEquipos[$i - 5];
        }

        foreach ($rondasEquipos as $numRonda => $partidos) {

            $ronda = Ronda::where('numero', $numRonda)->first();

            if (!$ronda) {
                $this->command->error("Ronda no encontrada: {$numRonda}");
                continue;
            }

            foreach ($partidos as $partidoIndex => $partido) {

                [$equipoLocal, $equipoVisitante] = $partido;

                // Rotación estaciones
                $estacion = ($partidoIndex + $numRonda - 1) % 3 + 1;

                foreach ($tableros as $mesaIndex => $tablero) {

                    if ($numRonda <= 5) {

                        $localTablero = $tablero;
                        $visitanteTablero = $tablero;

                    } else {

                        // Cruce de tableros segunda vuelta
                        $visitanteTablero = match($tablero) {
                            'A' => 'B',
                            'B' => 'A',
                            'C' => 'D',
                            'D' => 'C',
                        };

                        $localTablero = $tablero;
                    }

                    // Alternar colores
                    $color = ($numRonda % 2 == 1)
                        ? $colores[$tablero]
                        : ($colores[$tablero] == 'blancas' ? 'negras' : 'blancas');

                    if ($color == 'blancas') {

                        $blancas = $getJ(
                            $this->getJugadorNombre($equipoLocal, $localTablero)
                        );

                        $negras = $getJ(
                            $this->getJugadorNombre($equipoVisitante, $visitanteTablero)
                        );

                    } else {

                        $blancas = $getJ(
                            $this->getJugadorNombre($equipoVisitante, $visitanteTablero)
                        );

                        $negras = $getJ(
                            $this->getJugadorNombre($equipoLocal, $localTablero)
                        );
                    }

                    if (!$blancas || !$negras) {
                        continue;
                    }

                    Emparejamiento::firstOrCreate(

                        [
                            'ronda_id' => $ronda->id,
                            'mesa' => $mesaIndex + 1,
                            'estacion' => $estacion,
                        ],

                        [
                            'blancas_id' => $blancas,
                            'negras_id' => $negras,
                            'resultado' => null,
                        ]
                    );
                }
            }
        }
    }

    private function getJugadorNombre($equipo, $tablero)
    {
        $jugadores = [

            'Los Campeones' => [
                'A'=>'Daniel Solis',
                'B'=>'David Nolasco',
                'C'=>'Carla Blanco',
                'D'=>'Andrea Roblero'
            ],

            'Changos FC' => [
                'A'=>'Joaquin Mendez',
                'B'=>'Abner Utuy',
                'C'=>'Esteban Abril',
                'D'=>'Nahil Ortiz'
            ],

            'Gambitos' => [
                'A'=>'Juan Diego Pacheco',
                'B'=>'Mario',
                'C'=>'Joshua',
                'D'=>'Alejandra Abril'
            ],

            'Bloops' => [
                'A'=>'David Najera',
                'B'=>'Christopher Diaz',
                'C'=>'Ajbe Ortiz',
                'D'=>'Fernando Jolon'
            ],

            'Apertura Maestra' => [
                'A'=>'Steven Acevedo',
                'B'=>'Andres Gomez',
                'C'=>'Celeste Mendez',
                'D'=>'Mateo Roblero'
            ],

            'Gambito de Dama' => [
                'A'=>'Edgar Gonzalez',
                'B'=>'Carlos Esteban',
                'C'=>'Emiliano Pacheco',
                'D'=>'Saqmuj Aguilar'
            ],
        ];

        return $jugadores[$equipo][$tablero];
    }
}

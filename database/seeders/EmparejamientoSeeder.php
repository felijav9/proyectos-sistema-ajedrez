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

        // Secuencia de colores inicial por tablero
        $colores = ['A' => 'blancas', 'B' => 'negras', 'C' => 'blancas', 'D' => 'negras'];

        // Tableros
        $tableros = ['A','B','C','D'];

        // Datos simplificados: [ronda => [[equipoLocal, equipoVisitante]]]
        $rondasEquipos = [
            1 => [
                ['Bloops','Gambito de Dama'],
                ['Changos FC','Gambitos'],
                ['Apertura Maestra','Los Campeones'],
            ],
            2 => [
                ['Bloops','Gambitos'],
                ['Gambito de Dama','Apertura Maestra'],
                ['Changos FC','Los Campeones'],
            ],
            3 => [
                ['Bloops','Los Campeones'],
                ['Gambitos','Apertura Maestra'],
                ['Gambito de Dama','Changos FC'],
            ],
            4 => [
                ['Bloops','Changos FC'],
                ['Gambitos','Los Campeones'],
                ['Gambito de Dama','Apertura Maestra'],
            ],
            5 => [
                ['Gambitos','Gambito de Dama'],
                ['Apertura Maestra','Bloops'],
                ['Los Campeones','Changos FC'],
            ],
        ];

        // Repetir rondas 1-5 como 6-10
        for ($i = 6; $i <= 10; $i++) {
            $rondasEquipos[$i] = $rondasEquipos[$i - 5];
        }

        foreach ($rondasEquipos as $numRonda => $partidos) {
            $ronda = Ronda::where('numero', $numRonda)->first();

            foreach ($partidos as $partidoIndex => $partido) {
                [$equipoLocal, $equipoVisitante] = $partido;

                // Calculamos estación alternando entre 1,2,3
                $estacion = ($partidoIndex + $numRonda - 1) % 3 + 1;

                foreach ($tableros as $mesaIndex => $tablero) {
                    if ($numRonda <= 5) {
                        $localTablero = $tablero;
                        $visitanteTablero = $tablero;
                    } else {
                        // Alterna A vs B, B vs A, C vs D, D vs C
                        $visitanteTablero = match($tablero) {
                            'A' => 'B',
                            'B' => 'A',
                            'C' => 'D',
                            'D' => 'C',
                        };
                        $localTablero = $tablero;
                    }

                    // Alternar colores por ronda
                    $color = ($numRonda % 2 == 1) ? $colores[$tablero] : ($colores[$tablero] == 'blancas' ? 'negras' : 'blancas');

                    if ($color == 'blancas') {
                        $blancas = $getJ($this->getJugadorNombre($equipoLocal, $localTablero));
                        $negras = $getJ($this->getJugadorNombre($equipoVisitante, $visitanteTablero));
                    } else {
                        $blancas = $getJ($this->getJugadorNombre($equipoVisitante, $visitanteTablero));
                        $negras = $getJ($this->getJugadorNombre($equipoLocal, $localTablero));
                    }

                    Emparejamiento::firstOrCreate([
                        'ronda_id' => $ronda->id,
                        'blancas_id' => $blancas,
                        'negras_id' => $negras,
                        'mesa' => $mesaIndex + 1,
                        'estacion' => $estacion,
                        'resultado' => null,
                    ]);
                }
            }
        }
    }



    // Función para obtener el nombre del jugador según el equipo y tablero
    private function getJugadorNombre($equipo, $tablero)
    {
        $jugadores = [
            'Los Campeones' => ['A'=>'Daniel Solis','B'=>'David Nolasco','C'=>'Carla Blanco','D'=>'Andrea Roblero'],
            'Changos FC' => ['A'=>'Joaquin Mendez','B'=>'Abner Utuy','C'=>'Esteban Abril','D'=>'Nahil Ortiz'],
            'Gambitos' => ['A'=>'Juan Diego Pacheco','B'=>'Mario','C'=>'Joshua','D'=>'Alejandra Abril'],
            'Bloops' => ['A'=>'David Najera','B'=>'Christopher Diaz','C'=>'Ajbe Ortiz','D'=>'Fernando Jolon'],
            'Apertura Maestra' => ['A'=>'Steven Acevedo','B'=>'Andres Gomez','C'=>'Celeste Mendez','D'=>'Mateo Roblero'],
            'Gambito de Dama' => ['A'=>'Edgar Gonzalez','B'=>'Carlos Esteban','C'=>'Emiliano Pacheco','D'=>'Saqmuj Aguilar'],
        ];

        return $jugadores[$equipo][$tablero];
    }
}
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
        // Helper para obtener ID de jugador por nombre y evitar errores
        $getJ = function ($nombre) {
            $jugador = Jugador::where('nombre', trim($nombre))->first();
            if (!$jugador) {
                $this->command->error("Jugador no encontrado: {$nombre}");
                return null;
            }
            return $jugador->id;
        };

        $data = [
            1 => [
                ['e' => 1, 'm' => 1, 'b' => 'David Najera', 'n' => 'Joaquin Mendez'],
                ['e' => 1, 'm' => 2, 'b' => 'Abner Utuy', 'n' => 'Christopher Diaz'],
                ['e' => 1, 'm' => 3, 'b' => 'Ajbe Ortiz', 'n' => 'Esteban Abril'],
                ['e' => 1, 'm' => 4, 'b' => 'Nahil Ortiz', 'n' => 'Fernando Jolon'],
                ['e' => 2, 'm' => 5, 'b' => 'Steven Acevedo', 'n' => 'Juan Diego Pacheco'],
                ['e' => 2, 'm' => 6, 'b' => 'Mario', 'n' => 'Andres Gomez'],
                ['e' => 2, 'm' => 7, 'b' => 'Celeste Mendez', 'n' => 'Joshua'],
                ['e' => 2, 'm' => 8, 'b' => 'Alejandra Abril', 'n' => 'Mateo Roblero'],
                ['e' => 3, 'm' => 9, 'b' => 'Edgar Gonzalez', 'n' => 'Daniel Solis'],
                ['e' => 3, 'm' => 10, 'b' => 'David Nolasco', 'n' => 'Carlos Esteban'],
                ['e' => 3, 'm' => 11, 'b' => 'Emiliano Pacheco', 'n' => 'Carla Blanco'],
                ['e' => 3, 'm' => 12, 'b' => 'Andrea Roblero', 'n' => 'Saqmuj Aguilar'],
            ],
            2 => [
                ['e' => 1, 'm' => 1, 'b' => 'Steven Acevedo', 'n' => 'Joaquin Mendez'],
                ['e' => 1, 'm' => 2, 'b' => 'Abner Utuy', 'n' => 'Andres Gomez'],
                ['e' => 1, 'm' => 3, 'b' => 'Celeste Mendez', 'n' => 'Esteban Abril'],
                ['e' => 1, 'm' => 4, 'b' => 'Nahil Ortiz', 'n' => 'Mateo Roblero'],
                ['e' => 2, 'm' => 5, 'b' => 'David Najera', 'n' => 'Daniel Solis'],
                ['e' => 2, 'm' => 6, 'b' => 'David Nolasco', 'n' => 'Christopher Diaz'],
                ['e' => 2, 'm' => 7, 'b' => 'Ajbe Ortiz', 'n' => 'Carla Blanco'],
                ['e' => 2, 'm' => 8, 'b' => 'Andrea Roblero', 'n' => 'Fernando Jolon'],
                ['e' => 3, 'm' => 9, 'b' => 'Edgar Gonzalez', 'n' => 'Juan Diego Pacheco'],
                ['e' => 3, 'm' => 10, 'b' => 'Mario', 'n' => 'Carlos Esteban'],
                ['e' => 3, 'm' => 11, 'b' => 'Emiliano Pacheco', 'n' => 'Joshua'],
                ['e' => 3, 'm' => 12, 'b' => 'Alejandra Abril', 'n' => 'Saqmuj Aguilar'],
            ],
            3 => [
                ['e' => 1, 'm' => 1, 'b' => 'Daniel Solis', 'n' => 'Juan Diego Pacheco'],
                ['e' => 1, 'm' => 2, 'b' => 'Mario', 'n' => 'David Nolasco'],
                ['e' => 1, 'm' => 3, 'b' => 'Carla Blanco', 'n' => 'Joshua'],
                ['e' => 1, 'm' => 4, 'b' => 'Alejandra Abril', 'n' => 'Andrea Roblero'],
                ['e' => 2, 'm' => 5, 'b' => 'Edgar Gonzalez', 'n' => 'Joaquin Mendez'],
                ['e' => 2, 'm' => 6, 'b' => 'Abner Utuy', 'n' => 'Carlos Esteban'],
                ['e' => 2, 'm' => 7, 'b' => 'Emiliano Pacheco', 'n' => 'Esteban Abril'],
                ['e' => 2, 'm' => 8, 'b' => 'Nahil Ortiz', 'n' => 'Saqmuj Aguilar'],
                ['e' => 3, 'm' => 9, 'b' => 'David Najera', 'n' => 'Steven Acevedo'],
                ['e' => 3, 'm' => 10, 'b' => 'Andres Gomez', 'n' => 'Christopher Diaz'],
                ['e' => 3, 'm' => 11, 'b' => 'Ajbe Ortiz', 'n' => 'Celeste Mendez'],
                ['e' => 3, 'm' => 12, 'b' => 'Mateo Roblero', 'n' => 'Fernando Jolon'],
            ],
            4 => [
                ['e' => 1, 'm' => 1, 'b' => 'Daniel Solis', 'n' => 'Steven Acevedo'],
                ['e' => 1, 'm' => 2, 'b' => 'Andres Gomez', 'n' => 'David Nolasco'],
                ['e' => 1, 'm' => 3, 'b' => 'Carla Blanco', 'n' => 'Celeste Mendez'],
                ['e' => 1, 'm' => 4, 'b' => 'Mateo Roblero', 'n' => 'Andrea Roblero'],
                ['e' => 2, 'm' => 5, 'b' => 'David Najera', 'n' => 'Juan Diego Pacheco'],
                ['e' => 2, 'm' => 6, 'b' => 'Mario', 'n' => 'Christopher Diaz'],
                ['e' => 2, 'm' => 7, 'b' => 'Ajbe Ortiz', 'n' => 'Joshua'],
                ['e' => 2, 'm' => 8, 'b' => 'Alejandra Abril', 'n' => 'Fernando Jolon'],
                ['e' => 3, 'm' => 9, 'b' => 'Edgar Gonzalez', 'n' => 'Joaquin Mendez'],
                ['e' => 3, 'm' => 10, 'b' => 'Abner Utuy', 'n' => 'Carlos Esteban'],
                ['e' => 3, 'm' => 11, 'b' => 'Emiliano Pacheco', 'n' => 'Esteban Abril'],
                ['e' => 3, 'm' => 12, 'b' => 'Nahil Ortiz', 'n' => 'Saqmuj Aguilar'],
            ],
            5 => [
                ['e' => 1, 'm' => 1, 'b' => 'Juan Diego Pacheco', 'n' => 'Edgar Gonzalez'],
                ['e' => 1, 'm' => 2, 'b' => 'Carlos Esteban', 'n' => 'Mario'],
                ['e' => 1, 'm' => 3, 'b' => 'Joshua', 'n' => 'Emiliano Pacheco'],
                ['e' => 1, 'm' => 4, 'b' => 'Saqmuj Aguilar', 'n' => 'Alejandra Abril'],
                ['e' => 2, 'm' => 5, 'b' => 'Steven Acevedo', 'n' => 'David Najera'],
                ['e' => 2, 'm' => 6, 'b' => 'Christopher Diaz', 'n' => 'Andres Gomez'],
                ['e' => 2, 'm' => 7, 'b' => 'Celeste Mendez', 'n' => 'Ajbe Ortiz'],
                ['e' => 2, 'm' => 8, 'b' => 'Fernando Jolon', 'n' => 'Mateo Roblero'],
                ['e' => 3, 'm' => 9, 'b' => 'Daniel Solis', 'n' => 'Joaquin Mendez'],
                ['e' => 3, 'm' => 10, 'b' => 'Abner Utuy', 'n' => 'David Nolasco'],
                ['e' => 3, 'm' => 11, 'b' => 'Carla Blanco', 'n' => 'Esteban Abril'],
                ['e' => 3, 'm' => 12, 'b' => 'Nahil Ortiz', 'n' => 'Andrea Roblero'],
            ],
            6 => [
                ['e' => 1, 'm' => 1, 'b' => 'Abner Utuy', 'n' => 'David Najera'],
                ['e' => 1, 'm' => 2, 'b' => 'Christopher Diaz', 'n' => 'Joaquin Mendez'],
                ['e' => 1, 'm' => 3, 'b' => 'Nahil Ortiz', 'n' => 'Ajbe Ortiz'],
                ['e' => 1, 'm' => 4, 'b' => 'Fernando Jolon', 'n' => 'Esteban Abril'],
                ['e' => 2, 'm' => 5, 'b' => 'Mario', 'n' => 'Steven Acevedo'],
                ['e' => 2, 'm' => 6, 'b' => 'Andres Gomez', 'n' => 'Juan Diego Pacheco'],
                ['e' => 2, 'm' => 7, 'b' => 'Alejandra Abril', 'n' => 'Celeste Mendez'],
                ['e' => 2, 'm' => 8, 'b' => 'Mateo Roblero', 'n' => 'Joshua'],
                ['e' => 3, 'm' => 9, 'b' => 'David Nolasco', 'n' => 'Edgar Gonzalez'],
                ['e' => 3, 'm' => 10, 'b' => 'Carlos Esteban', 'n' => 'Daniel Solis'],
                ['e' => 3, 'm' => 11, 'b' => 'Andrea Roblero', 'n' => 'Emiliano Pacheco'],
                ['e' => 3, 'm' => 12, 'b' => 'Saqmuj Aguilar', 'n' => 'Carla Blanco'],
            ],
            7 => [
                ['e' => 1, 'm' => 1, 'b' => 'Joaquin Mendez', 'n' => 'Andres Gomez'],
                ['e' => 1, 'm' => 2, 'b' => 'Steven Acevedo', 'n' => 'Abner Utuy'],
                ['e' => 1, 'm' => 3, 'b' => 'Esteban Abril', 'n' => 'Mateo Roblero'],
                ['e' => 1, 'm' => 4, 'b' => 'Celeste Mendez', 'n' => 'Nahil Ortiz'],
                ['e' => 2, 'm' => 5, 'b' => 'Daniel Solis', 'n' => 'Christopher Diaz'],
                ['e' => 2, 'm' => 6, 'b' => 'David Najera', 'n' => 'David Nolasco'],
                ['e' => 2, 'm' => 7, 'b' => 'Carla Blanco', 'n' => 'Fernando Jolon'],
                ['e' => 2, 'm' => 8, 'b' => 'Ajbe Ortiz', 'n' => 'Andrea Roblero'],
                ['e' => 3, 'm' => 9, 'b' => 'Juan Diego Pacheco', 'n' => 'Carlos Esteban'],
                ['e' => 3, 'm' => 10, 'b' => 'Edgar Gonzalez', 'n' => 'Mario'],
                ['e' => 3, 'm' => 11, 'b' => 'Joshua', 'n' => 'Saqmuj Aguilar'],
                ['e' => 3, 'm' => 12, 'b' => 'Emiliano Pacheco', 'n' => 'Alejandra Abril'],
            ],
            8 => [
                ['e' => 1, 'm' => 1, 'b' => 'Mario', 'n' => 'Daniel Solis'],
                ['e' => 1, 'm' => 2, 'b' => 'David Nolasco', 'n' => 'Juan Diego Pacheco'],
                ['e' => 1, 'm' => 3, 'b' => 'Alejandra Abril', 'n' => 'Carla Blanco'],
                ['e' => 1, 'm' => 4, 'b' => 'Andrea Roblero', 'n' => 'Joshua'],
                ['e' => 2, 'm' => 5, 'b' => 'Abner Utuy', 'n' => 'Edgar Gonzalez'],
                ['e' => 2, 'm' => 6, 'b' => 'Carlos Esteban', 'n' => 'Joaquin Mendez'],
                ['e' => 2, 'm' => 7, 'b' => 'Nahil Ortiz', 'n' => 'Emiliano Pacheco'],
                ['e' => 2, 'm' => 8, 'b' => 'Saqmuj Aguilar', 'n' => 'Esteban Abril'],
                ['e' => 3, 'm' => 9, 'b' => 'Andres Gomez', 'n' => 'David Najera'],
                ['e' => 3, 'm' => 10, 'b' => 'Christopher Diaz', 'n' => 'Steven Acevedo'],
                ['e' => 3, 'm' => 11, 'b' => 'Mateo Roblero', 'n' => 'Ajbe Ortiz'],
                ['e' => 3, 'm' => 12, 'b' => 'Fernando Jolon', 'n' => 'Celeste Mendez'],
            ],
            9 => [
                ['e' => 1, 'm' => 1, 'b' => 'Steven Acevedo', 'n' => 'David Nolasco'],
                ['e' => 1, 'm' => 2, 'b' => 'Daniel Solis', 'n' => 'Andres Gomez'],
                ['e' => 1, 'm' => 3, 'b' => 'Celeste Mendez', 'n' => 'Andrea Roblero'],
                ['e' => 1, 'm' => 4, 'b' => 'Carla Blanco', 'n' => 'Mateo Roblero'],
                ['e' => 2, 'm' => 5, 'b' => 'Juan Diego Pacheco', 'n' => 'Christopher Diaz'],
                ['e' => 2, 'm' => 6, 'b' => 'David Najera', 'n' => 'Mario'],
                ['e' => 2, 'm' => 7, 'b' => 'Joshua', 'n' => 'Fernando Jolon'],
                ['e' => 2, 'm' => 8, 'b' => 'Ajbe Ortiz', 'n' => 'Alejandra Abril'],
                ['e' => 3, 'm' => 9, 'b' => 'Joaquin Mendez', 'n' => 'Carlos Esteban'],
                ['e' => 3, 'm' => 10, 'b' => 'Edgar Gonzalez', 'n' => 'Abner Utuy'],
                ['e' => 3, 'm' => 11, 'b' => 'Esteban Abril', 'n' => 'Saqmuj Aguilar'],
                ['e' => 3, 'm' => 12, 'b' => 'Emiliano Pacheco', 'n' => 'Nahil Ortiz'],
            ],
            10 => [
                ['e' => 1, 'm' => 1, 'b' => 'Carlos Esteban', 'n' => 'Juan Diego Pacheco'],
                ['e' => 1, 'm' => 2, 'b' => 'Mario', 'n' => 'Edgar Gonzalez'],
                ['e' => 1, 'm' => 3, 'b' => 'Saqmuj Aguilar', 'n' => 'Joshua'],
                ['e' => 1, 'm' => 4, 'b' => 'Alejandra Abril', 'n' => 'Emiliano Pacheco'],
                ['e' => 2, 'm' => 5, 'b' => 'Christopher Diaz', 'n' => 'Steven Acevedo'],
                ['e' => 2, 'm' => 6, 'b' => 'Andres Gomez', 'n' => 'David Najera'],
                ['e' => 2, 'm' => 7, 'b' => 'Fernando Jolon', 'n' => 'Celeste Mendez'],
                ['e' => 2, 'm' => 8, 'b' => 'Mateo Roblero', 'n' => 'Ajbe Ortiz'],
                ['e' => 3, 'm' => 9, 'b' => 'Abner Utuy', 'n' => 'Daniel Solis'],
                ['e' => 3, 'm' => 10, 'b' => 'David Nolasco', 'n' => 'Joaquin Mendez'],
                ['e' => 3, 'm' => 11, 'b' => 'Nahil Ortiz', 'n' => 'Carla Blanco'],
                ['e' => 3, 'm' => 12, 'b' => 'Andrea Roblero', 'n' => 'Esteban Abril'],
            ],
        ];

        foreach ($data as $numRonda => $emparejamientos) {
            $ronda = Ronda::where('numero', $numRonda)->first();

            foreach ($emparejamientos as $emp) {
                $blancasId = $getJ($emp['b']);
                $negrasId = $getJ($emp['n']);

                if ($blancasId && $negrasId) {
                    Emparejamiento::create([
                        'ronda_id' => $ronda->id,
                        'blancas_id' => $blancasId,
                        'negras_id' => $negrasId,
                        'mesa' => $emp['m'],
                        'estacion' => $emp['e'], 
                        'resultado' => null,
                    ]);
                }
            }
        }
    }
}

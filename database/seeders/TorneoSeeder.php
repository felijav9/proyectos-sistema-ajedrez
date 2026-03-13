<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Torneo;

class TorneoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */


    public function run(): void
{
    // Definimos una lista de torneos (cada uno es un array)
    $torneos = [
        [
            'nombre' => 'Torneo de Verano por equipos marzo 2026',
            'fecha'  => '2026-03-28',
            'tipo'   => 'equipos',
        ],
    ];

    // si no existe por el nombre lo creamos
    foreach ($torneos as $datos) {
        Torneo::firstOrCreate(
            ['nombre' => $datos['nombre']],
            $datos
        );
    }

    
}

}

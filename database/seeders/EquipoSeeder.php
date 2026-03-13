<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Equipo;

class EquipoSeeder extends Seeder
{
    public function run(): void
    {
        $equipos = [
            'Los Campeones',
            'Changos FC',
            'Gambitos',
            'Bloops',
            'Apertura Maestra',
            'Gambito de Dama'
        ];

        foreach ($equipos as $nombre) {
            Equipo::firstOrCreate([
                'nombre' => $nombre,
                'torneo_id' => 1 
            ]);
        }
    }
}

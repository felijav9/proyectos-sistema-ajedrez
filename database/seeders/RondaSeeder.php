<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ronda;

class RondaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            Ronda::firstOrCreate([
                'numero' => $i,
                'torneo_id' => 1
            ], [
                'fecha_inicio' => null
            ]);
        }
    }
}

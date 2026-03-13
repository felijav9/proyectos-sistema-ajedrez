<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Emparejamiento extends Model
{
    use HasFactory;

    // Campos que permitimos llenar desde el Seeder o formularios
    protected $fillable = [
        'ronda_id',
        'blancas_id',
        'negras_id',
        'mesa',
        'estacion',
        'resultado',
    ];

    /**
     * Relación con la Ronda
     */
    public function ronda(): BelongsTo
    {
        return $this->belongsTo(Ronda::class);
    }

    /**
     * Relación con el Jugador que lleva piezas blancas
     */
    public function jugadorBlancas(): BelongsTo
    {
        return $this->belongsTo(Jugador::class, 'blancas_id');
    }

    /**
     * Relación con el Jugador que lleva piezas negras
     */
    public function jugadorNegras(): BelongsTo
    {
        return $this->belongsTo(Jugador::class, 'negras_id');
    }
}

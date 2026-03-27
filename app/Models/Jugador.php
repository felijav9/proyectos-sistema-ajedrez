<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jugador extends Model
{
    use HasFactory;

    // Definimos la tabla si el nombre no es el plural automático (opcional)
    protected $table = 'jugadores';

    // Campos que permitimos llenar mediante el Seeder o formularios
    protected $fillable = [
        'equipo_id',
        'nombre',
        'tablero',
        'elo',
        'edad',
        'genero'
    ];

    /**
     * Relación: Un jugador pertenece a un equipo.
     */
    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    /**
     * Relación: Un jugador puede tener muchos emparejamientos
     * (ya sea como blancas o como negras).
     */
    public function partidasBlancas()
    {
        return $this->hasMany(Emparejamiento::class, 'blancas_id');
    }

    public function partidasNegras()
    {
        return $this->hasMany(Emparejamiento::class, 'negras_id');
    }
}

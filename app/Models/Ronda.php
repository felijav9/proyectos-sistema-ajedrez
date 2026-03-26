<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ronda extends Model
{
    use HasFactory;

    protected $fillable = ['numero', 'fecha_inicio', 'torneo_id'];

    public function torneo()
    {
        return $this->belongsTo(Torneo::class);
    }

    public function emparejamientos()
    {
        return $this->hasMany(Emparejamiento::class);
    }

     public function resultadosEquipos()
    {
        return $this->hasMany(ResultadoEquipo::class);
    }
}

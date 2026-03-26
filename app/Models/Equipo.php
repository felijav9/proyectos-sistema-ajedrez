<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Equipo extends Model
{
    use HasFactory;

    protected $fillable = ['torneo_id', 'nombre', 'capitan'];
    public function torneo()
    {
        return $this->belongsTo(Torneo::class);
    }

    // Relación: Un equipo tiene muchos jugadores
    public function jugadores()
    {
        return $this->hasMany(Jugador::class);
    }

    public function resultados()
    {
        return $this->hasMany(ResultadoEquipo::class);
    }

    public function getPuntosGlobalesAttribute()
    {
        return $this->resultados()->sum('puntos_globales');
    }

    public function getPuntosIndividualesAttribute()
    {
        return $this->resultados()->sum('puntos_individuales');
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Torneo extends Model
{
    protected $fillable = ['nombre', 'fecha', 'tipo'];

    public function rondas()
    {
        return $this->hasMany(Ronda::class);
    }

    public function equipos()
    {
        return $this->hasMany(Equipo::class);
    }

}

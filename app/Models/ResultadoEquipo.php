<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultadoEquipo extends Model
{
    protected $table = 'resultados_equipos';

    protected $fillable = [
        'ronda_id',
        'equipo_id',
        'puntos_individuales', 
        'puntos_globales'
    ];

    public function ronda()
    {
        return $this->belongsTo(Ronda::class);
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }
}

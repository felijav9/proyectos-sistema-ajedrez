<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Emparejamiento extends Model
{
    use HasFactory;

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
     * Jugador con blancas
     */
    public function jugadorBlancas(): BelongsTo
    {
        return $this->belongsTo(Jugador::class, 'blancas_id');
    }

    /**
     * Jugador con negras
     */
    public function jugadorNegras(): BelongsTo
    {
        return $this->belongsTo(Jugador::class, 'negras_id');
    }

    /**
     * Equipo de blancas (clave para cálculo por equipos)
     */
    public function equipoBlancas()
    {
        return $this->jugadorBlancas->equipo ?? null;
    }

    /**
     * Equipo de negras
     */
    public function equipoNegras()
    {
        return $this->jugadorNegras->equipo ?? null;
    }

    /**
     * Puntos para blancas
     */
    public function getPuntosBlancasAttribute()
    {
        return match ($this->resultado) {
            '1-0' => 1,
            '0-1' => 0,
            '0.5-0.5', '1-1' => 0.5,
            default => 0
        };
    }

    /**
     * Puntos para negras
     */
    public function getPuntosNegrasAttribute()
    {
        return match ($this->resultado) {
            '1-0' => 0,
            '0-1' => 1,
            '0.5-0.5', '1-1' => 0.5,
            default => 0
        };
    }

    /**
     * Saber si ya se jugó
     */
    public function getEstaJugadoAttribute(): bool
    {
        return !is_null($this->resultado);
    }

    /**
     * Resultado en formato numérico útil
     */
    public function getResultadoNumericoAttribute(): ?array
    {
        if (!$this->resultado) return null;

        return [
            'blancas' => $this->puntos_blancas,
            'negras' => $this->puntos_negras,
        ];
    }
}

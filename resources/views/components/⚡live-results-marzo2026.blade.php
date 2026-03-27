<?php

use Livewire\Component;
use App\Models\Torneo;
use App\Models\Emparejamiento;
use App\Models\Equipo;
use App\Models\ResultadoEquipo;

new class extends Component {
    public $openEquipos = false;
    public $torneo;
    public $equipos;
    public $emparejamientos = [];
    public $rondaSeleccionada = null;
    public $openModal = false;

    public $editandoEquipoId = null;
    public $nuevoNombre;

    public $editandoJugadorId = null;
    public $nuevoNombreJugador;

    public function mount()
    {
        $this->torneo = Torneo::with('rondas')->find(1);

        // cargar equipos con jugadores
        $this->equipos = Equipo::with('jugadores')->get();
    }

    public function verRonda($rondaId, $numero)
    {
        $this->rondaSeleccionada = $numero;

        $this->emparejamientos = Emparejamiento::with(['jugadorBlancas.equipo', 'jugadorNegras.equipo'])
            ->where('ronda_id', $rondaId)
            ->orderBy('estacion')
            ->orderBy('mesa')
            ->get();

        $this->openModal = true;
    }

    public function cerrarModal()
    {
        $this->openModal = false;
    }

    public function guardarResultado($id, $resultado)
    {
        $emp = Emparejamiento::find($id);
        $emp->resultado = $resultado;
        $emp->save();

        $this->calcularRonda($emp->ronda_id);

        $this->verRonda($emp->ronda_id, $this->rondaSeleccionada);
    }

    public function calcularRonda($rondaId)
    {
        $emparejamientos = Emparejamiento::with(['jugadorBlancas.equipo', 'jugadorNegras.equipo'])
            ->where('ronda_id', $rondaId)
            ->get()
            ->groupBy('estacion'); // CLAVE

        foreach ($emparejamientos as $estacion => $partidas) {
            $equipos = [];

            foreach ($partidas as $emp) {
                if (!$emp->resultado) {
                    continue;
                }

                $equipoBlancas = $emp->jugadorBlancas->equipo_id;
                $equipoNegras = $emp->jugadorNegras->equipo_id;

                $equipos[$equipoBlancas] = ($equipos[$equipoBlancas] ?? 0) + $emp->puntos_blancas;
                $equipos[$equipoNegras] = ($equipos[$equipoNegras] ?? 0) + $emp->puntos_negras;
            }

            // guardar resultados
            foreach ($equipos as $equipoId => $puntosIndividuales) {
                ResultadoEquipo::updateOrCreate(
                    [
                        'ronda_id' => $rondaId,
                        'equipo_id' => $equipoId,
                    ],
                    [
                        'puntos_individuales' => $puntosIndividuales,
                        'puntos_globales' => 0,
                    ],
                );
            }

            // calcular globales SOLO entre esos 2 equipos
            if (count($equipos) == 2) {
                $ids = array_keys($equipos);

                $a = ResultadoEquipo::where('ronda_id', $rondaId)->where('equipo_id', $ids[0])->first();
                $b = ResultadoEquipo::where('ronda_id', $rondaId)->where('equipo_id', $ids[1])->first();

                if ($a->puntos_individuales > $b->puntos_individuales) {
                    $a->update(['puntos_globales' => 2]);
                    $b->update(['puntos_globales' => 0]);
                } elseif ($a->puntos_individuales < $b->puntos_individuales) {
                    $a->update(['puntos_globales' => 0]);
                    $b->update(['puntos_globales' => 2]);
                } else {
                    $a->update(['puntos_globales' => 1]);
                    $b->update(['puntos_globales' => 1]);
                }
            }
        }
    }

    public function getTablaGeneralProperty()
    {
        $equipos = Equipo::with(['resultados'])->get();

        return $equipos
            ->map(function ($equipo) {
                $porRonda = [];

                foreach ($this->torneo->rondas as $ronda) {
                    $res = $equipo->resultados->where('ronda_id', $ronda->id)->first();

                    $porRonda[$ronda->numero] = [
                        'global' => $res->puntos_globales ?? null,
                        'individual' => $res->puntos_individuales ?? null,
                    ];
                }

                return [
                    'equipo' => $equipo,
                    'rondas' => $porRonda,
                    'total_global' => $equipo->puntos_globales,
                    'total_individual' => $equipo->puntos_individuales,
                ];
            })
            ->sortBy([['total_global', 'desc'], ['total_individual', 'desc']]);
    }

    public function getTablaIndividualProperty()
    {
        $jugadores = collect();

        foreach ($this->equipos as $equipo) {
            foreach ($equipo->jugadores as $jugador) {
                $porRonda = [];
                foreach ($this->torneo->rondas as $ronda) {
                    $emp = Emparejamiento::where('ronda_id', $ronda->id)
                        ->where(function ($q) use ($jugador) {
                            $q->where('blancas_id', $jugador->id)->orWhere('negras_id', $jugador->id);
                        })
                        ->first();

                    $puntos = 0;
                    if ($emp) {
                        $puntos = $emp->blancas_id == $jugador->id ? $emp->puntos_blancas : $emp->puntos_negras;
                    }

                    $porRonda[$ronda->numero] = $puntos;
                }

                $jugadores->push([
                    'jugador' => $jugador,
                    'equipo' => $equipo,
                    'porRonda' => $porRonda,
                    'total' => array_sum($porRonda),
                ]);
            }
        }

        // ordenar por total descendente
        return $jugadores->sortByDesc('total');
    }

    public function getGraficaEquiposProperty()
    {
        // Obtenemos los datos y los ordenamos
        $data = $this->tablaGeneral
            ->values()
            ->sortByDesc(function ($row) {
                return [$row['total_global'], $row['total_individual']];
            })
            ->values();

        return $data->map(function ($row, $index) {
            $nombre = $row['equipo']->nombre;
            $nombreLower = strtolower($nombre);
            $posicion = $index + 1;

            // Verificamos si hay puntos (puedes usar total_global o total_individual)
            $tienePuntos = $row['total_global'] > 0 || $row['total_individual'] > 0;

            // LÓGICA MODIFICADA:
            // Si tiene puntos, usamos medallas para los top 3.
            // Si NO tiene puntos (está en 0), solo mostramos el número "#1", "#2", etc.
            if ($tienePuntos) {
                $prefijo = match ($posicion) {
                    1 => '🥇 #1 ',
                    2 => '🥈 #2 ',
                    3 => '🥉 #3 ',
                    default => "#$posicion ",
                };
            } else {
                $prefijo = "#$posicion ";
            }

            // --- Lógica de colores (se mantiene igual) ---
            $color = match (true) {
                str_contains($nombreLower, 'campeones') => '#dc2626',
                str_contains($nombreLower, 'bloops') => '#0f172a',
                str_contains($nombreLower, 'apertura maestra') => '#475569',
                str_contains($nombreLower, 'gambitos') && !str_contains($nombreLower, 'dama') => '#16a34a',
                str_contains($nombreLower, 'gambito de dama') => '#2563eb',
                str_contains($nombreLower, 'changos') => '#db2777',
                default => '#c5a059',
            };

            return [
                'nombre' => $prefijo . $nombre,
                'global' => $row['total_global'] ?? 0,
                'individual' => $row['total_individual'] ?? 0,
                'color' => $color,
            ];
        });
    }

    public function editarEquipo($id, $nombreActual)
    {
        $this->editandoEquipoId = $id;
        $this->nuevoNombre = $nombreActual;
    }

    public function cancelarEdicion()
    {
        $this->editandoEquipoId = null;
        $this->nuevoNombre = '';
    }

    public function actualizarNombreEquipo()
    {
        $this->validate([
            'nuevoNombre' => 'required|min:3|max:50',
        ]);

        $equipo = Equipo::find($this->editandoEquipoId);
        $equipo->nombre = $this->nuevoNombre;
        $equipo->save();

        // Refrescar la lista de equipos
        $this->equipos = Equipo::with('jugadores')->get();
        $this->cancelarEdicion();

        // Opcional: Notificación (si usas alguna librería)
        // $this->dispatch('notify', 'Equipo actualizado correctamente');
    }

    public function editarJugador($id, $nombreActual)
    {
        $this->editandoJugadorId = $id;
        $this->nuevoNombreJugador = $nombreActual;
        $this->editandoEquipoId = null; // Cerramos edición de equipo si estaba abierta
    }

    public function cancelarEdicionJugador()
    {
        $this->editandoJugadorId = null;
        $this->nuevoNombreJugador = '';
    }

    public function actualizarNombreJugador()
    {
        $this->validate([
            'nuevoNombreJugador' => 'required|min:3|max:50',
        ]);

        $jugador = \App\Models\Jugador::find($this->editandoJugadorId);
        $jugador->nombre = $this->nuevoNombreJugador;
        $jugador->save();

        // Refrescar datos
        $this->equipos = Equipo::with('jugadores')->get();
        $this->cancelarEdicionJugador();
    }

    public function getGraficaData()
    {
        // Esta función simplemente devuelve la propiedad que ya tienes armada
        return $this->graficaEquipos;
    }
};
?>

<div class="min-h-screen bg-slate-50 font-sans text-slate-900 pb-12">

    {{-- HEADER / TITULO --}}
    <header class="bg-[#c5a059] py-10 mb-10 shadow-lg relative overflow-hidden">
        <div class="absolute inset-0 opacity-10"
            style="background-image: radial-gradient(#000 1px, transparent 1px); background-size: 20px 20px;"></div>

        <div class="max-w-7xl mx-auto px-4 relative z-10">
            <h1 class="text-4xl md:text-5xl font-black text-white text-center uppercase tracking-tighter drop-shadow-md">
                Live results torneo por equipos marzo 2026
            </h1>
            <div class="w-24 h-1 bg-white mx-auto mt-4 rounded-full"></div>
        </div>
    </header>

    {{-- BANNER DE REGRESO - VERSIÓN AZUL PROFUNDO --}}
    <div class="back-banner-container">
        <a href="{{ route('torneo.index') }}" class="back-banner-link">
            <div class="banner-shine-soft"></div>

            <div class="banner-content-left">
                <div class="back-badge-blue">
                    <span class="icon-container">
                        <span class="arrow-back">←</span>
                    </span>
                    VOLVER
                </div>

                <div class="banner-text">
                    <span class="text-main-light">Página Principal</span>
                    <span class="text-sub-blue">Regresar al panel de emparejamientos</span>
                </div>
            </div>

            <div class="banner-content-right">
                <span class="btn-text-blue">Ir al Inicio</span>
                <div class="btn-arrow-blue">⌂</div>
            </div>
        </a>
    </div>

    <style>
        /* Contenedor */
        .back-banner-container {
            max-width: 80rem;
            margin: 0 auto 1.5rem auto;
            padding: 0 1rem;
        }

        /* Enlace principal (Azul Profundo) */
        .back-banner-link {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.25rem 2rem 0.25rem 0.25rem;
            background-color: #1e3a8a;
            /* Azul Rey Oscuro (Blue-900) */
            border-radius: 1rem;
            text-decoration: none;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 15px -3px rgba(30, 58, 138, 0.3);
            font-family: sans-serif;
        }

        .back-banner-link:hover {
            background-color: #1e40af;
            /* Un azul un poco más claro al hacer hover */
            transform: translateX(-8px);
            /* Efecto de retroceso más marcado */
            box-shadow: 0 20px 25px -5px rgba(30, 58, 138, 0.4);
        }

        /* Badge "VOLVER" */
        .back-badge-blue {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background-color: white;
            color: #1e3a8a;
            padding: 0.75rem 1.25rem;
            border-radius: 0.75rem;
            font-weight: 900;
            letter-spacing: 0.05em;
            z-index: 10;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Textos */
        .banner-content-left {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            z-index: 10;
        }

        .text-main-light {
            color: white;
            font-weight: 800;
            font-size: 1.125rem;
            text-transform: uppercase;
            letter-spacing: -0.02em;
        }

        .text-sub-blue {
            color: #bfdbfe;
            /* Azul muy claro (Blue-200) */
            font-size: 0.75rem;
            font-weight: 600;
            opacity: 0.8;
        }

        /* Botón derecha */
        .banner-content-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            z-index: 10;
        }

        .btn-text-blue {
            color: #dbeafe;
            font-weight: 800;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .btn-arrow-blue {
            width: 2.5rem;
            height: 2.5rem;
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }

        .back-banner-link:hover .btn-arrow-blue {
            background-color: white;
            color: #1e3a8a;
            transform: rotate(-10deg);
        }

        /* Brillo */
        .banner-shine-soft {
            position: absolute;
            top: 0;
            left: -100%;
            height: 100%;
            width: 50%;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.15), transparent);
            transform: skewX(-15deg);
        }

        .back-banner-link:hover .banner-shine-soft {
            animation: shine-effect 1.2s ease-in-out;
        }

        @keyframes shine-effect {
            100% {
                left: 150%;
            }
        }

        @media (max-width: 768px) {
            .banner-text {
                display: none;
            }
        }
    </style>


    <main class="max-w-7xl mx-auto px-4 py-8">
        {{-- SECCIÓN SUPERIOR: GRÁFICA Y TABLA (LADO A LADO) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">

            <section x-data="{
                chart: null,
                loading: false,
                puntosIndividual: [],
            
                init() {
                    this.renderOrUpdate();
            
                    document.addEventListener('livewire:initialized', () => {
                        @this.on('graficaActualizada', () => {
                            this.renderOrUpdate();
                        });
                    });
                },
            
                async refreshManual() {
                    this.loading = true;
                    try {
                        let freshData = await @this.getGraficaData();
                        this.renderOrUpdate(freshData);
                    } catch (e) {
                        console.error('Error al actualizar:', e);
                    } finally {
                        setTimeout(() => { this.loading = false; }, 600);
                    }
                },
            
                renderOrUpdate(incomingData = null) {
                    let data = incomingData ? incomingData : @js($this->graficaEquipos);
            
                    // Si no hay datos, limpiamos la gráfica y salimos
                    if (!data || data.length === 0) {
                        if (this.chart) { this.chart.destroy();
                            this.chart = null; }
                        this.$refs.mapaEquipos.innerHTML = '';
                        return;
                    }
            
                    let nombres = data.map(e => e.nombre);
                    let puntosGlobal = data.map(e => Number(e.global));
                    this.puntosIndividual = data.map(e => Number(e.individual));
                    let colores = data.map(e => e.color);
            
                    if (this.chart && typeof this.chart.destroy === 'function') {
                        this.chart.destroy();
                    }
            
                    this.$refs.mapaEquipos.innerHTML = '';
            
                    this.chart = new ApexCharts(this.$refs.mapaEquipos, {
                        chart: {
                            type: 'bar',
                            height: 450,
                            toolbar: { show: false },
                            fontFamily: 'inherit',
                            animations: { enabled: true, easing: 'easeinout', speed: 800 }
                        },
                        series: [{ name: 'Puntos Globales', data: puntosGlobal }],
                        colors: colores,
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                barHeight: '80%',
                                distributed: true,
                                borderRadius: 8,
                                dataLabels: { position: 'bottom', hideOverflowingText: false }
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            textAnchor: 'start',
                            style: { colors: ['#fff'], fontWeight: '800', fontSize: '10px' },
                            formatter: (val, opts) => {
                                let ind = this.puntosIndividual[opts.dataPointIndex];
                                let txtG = val === 1 ? val + ' PT GRUPAL' : val + ' PTS GRUPALES';
                                let txtI = ind === 1 ? ind + ' PT INDV' : ind + ' PTS INDV';
                                return (val < 6) ? [txtG, txtI] : txtG + '  |  ' + txtI;
                            },
                            offsetX: 0,
                            dropShadow: { enabled: true }
                        },
                        xaxis: {
                            categories: nombres,
                            labels: { style: { fontWeight: '700' } }
                        },
                        yaxis: {
                            labels: {
                                style: { colors: '#1e293b', fontWeight: '900', fontSize: '13px' },
                                maxWidth: 150
                            }
                        },
                        tooltip: {
                            theme: 'dark',
                            y: {
                                formatter: (val, opts) => {
                                    let ind = this.puntosIndividual[opts.dataPointIndex];
                                    return val + ' grupales | ' + ind + ' individuales';
                                }
                            }
                        },
                        legend: { show: false }
                    });
            
                    this.chart.render();
                }
            }"
                class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 overflow-hidden flex flex-col mb-16 w-full">

                <div class="flex flex-col mb-6">
                    <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight flex items-center gap-4">
                        <span class="text-[#c5a059] text-3xl">♜</span> Rendimiento de Equipos
                    </h2>

                    @php
                        // Verificamos si todos los equipos están en cero usando la misma lógica que tu tabla
                        $graficaEnCero = collect($this->graficaEquipos)->every(
                            fn($e) => $e['global'] == 0 && $e['individual'] == 0,
                        );
                    @endphp

                    @if ($graficaEnCero)
                        <span class="text-xs font-bold text-green-600 mt-1 flex items-center gap-1 ml-12">
                            <span class="relative flex h-2 w-2">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                            POR DEFINIRSE
                        </span>
                    @endif
                </div>

                <div class="relative bg-slate-50/50 rounded-2xl border border-slate-100 p-4">

                    <div class="absolute top-4 left-6 z-10">
                        <button @click="refreshManual()"
                            class="flex items-center gap-2 px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-500 text-[9px] font-black uppercase tracking-widest rounded-lg transition-all border border-slate-200 shadow-sm"
                            :disabled="loading">
                            <svg :class="loading ? 'animate-spin' : ''" class="w-3 h-3 text-[#c5a059]" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                </path>
                            </svg>
                            <span x-text="loading ? 'Cargando...' : 'Actualizar Datos'"></span>
                        </button>
                    </div>

                    <div class="mt-10" x-ref="mapaEquipos" wire:ignore></div>
                </div>
            </section>

            {{-- BLOQUE DERECHO: TABLA GENERAL (CON DISEÑO DE MEDALLAS) --}}
            <section
                class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 overflow-hidden flex flex-col mb-16"
                x-data="{ open: false }">
                @php
                    $tabla = $this->tablaGeneral->values();

                    $todosEnCero = $tabla->every(
                        fn($row) => $row['total_global'] == 0 && $row['total_individual'] == 0,
                    );

                    if (!function_exists('getMedallaStyleById')) {
                        function getMedallaStyleById($pos, $tienePuntos)
                        {
                            if (!$tienePuntos) {
                                return ['bg' => 'transparent', 'border' => 'transparent', 'totalText' => '#94a3b8'];
                            }
                            return match ($pos) {
                                1 => [
                                    'bg' => 'rgba(255, 215, 0, 0.25)',
                                    'border' => '#FFD700',
                                    'totalText' => '#b8860b',
                                ],
                                2 => [
                                    'bg' => 'rgba(192, 192, 192, 0.3)',
                                    'border' => '#94a3b8',
                                    'totalText' => '#64748b',
                                ],
                                3 => [
                                    'bg' => 'rgba(205, 127, 50, 0.25)',
                                    'border' => '#CD7F32',
                                    'totalText' => '#8b4513',
                                ],
                                default => [
                                    'bg' => 'transparent',
                                    'border' => 'transparent',
                                    'totalText' => '#c5a059',
                                ],
                            };
                        }
                    }
                @endphp

                <div @click="open = !open" class="flex items-center justify-between cursor-pointer group mb-6">
                    <div class="flex flex-col">
                        <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight flex items-center gap-3">
                            <span class="text-[#c5a059] text-3xl">♖</span> Tabla General Por Equipos
                        </h2>
                        {{-- Mensaje "Por definirse" dinámico --}}
                        @if ($todosEnCero)
                            <span class="text-xs font-bold text-green-600 mt-1 flex items-center gap-1">
                                <span class="relative flex h-2 w-2">
                                    <span
                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                </span>
                                POR DEFINIRSE
                            </span>
                        @endif
                    </div>

                    <span class="text-xs font-bold text-slate-400 group-hover:text-[#c5a059] transition-colors"
                        x-text="open ? 'OCULTAR DETALLES' : 'MOSTRAR DETALLES'"></span>
                </div>

                <div x-show="open" x-transition x-cloak class="overflow-hidden rounded-2xl border border-slate-100">
                    <div class="overflow-x-auto overflow-y-auto custom-scrollbar" style="max-height: 500px;">
                        <table class="w-full text-sm border-collapse min-w-[800px]">
                            <thead class="sticky top-0 bg-slate-900 text-white z-20">
                                <tr>
                                    <th class="p-4 text-center">#</th>
                                    <th class="p-4 text-left">Equipo</th>
                                    {{-- Rondas Dinámicas --}}
                                    @foreach ($torneo->rondas as $ronda)
                                        <th class="p-4 text-center whitespace-nowrap">R{{ $ronda->numero }}</th>
                                    @endforeach
                                    <th class="p-4 text-center">Global</th>
                                    <th class="p-4 text-center">Indiv.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($tabla as $index => $row)
                                    @php
                                        $pos = $index + 1;
                                        $tienePuntos = $row['total_global'] > 0;
                                        $currentStyle = getMedallaStyleById($pos, $tienePuntos);
                                        $rowStyle = "background-color: {$currentStyle['bg']} !important; border-left: 8px solid {$currentStyle['border']};";
                                    @endphp
                                    <tr style="{{ $rowStyle }}" class="transition hover:bg-white/40">
                                        <td class="p-4 text-center font-black text-xl">
                                            @if ($tienePuntos)
                                                {{ $pos == 1 ? '🥇' : ($pos == 2 ? '🥈' : ($pos == 3 ? '🥉' : $pos)) }}
                                            @else
                                                <span class="text-slate-300">{{ $pos }}</span>
                                            @endif
                                        </td>

                                        <td class="p-4 text-left font-bold text-slate-800">
                                            {{ $row['equipo']->nombre }}
                                        </td>

                                        @foreach ($row['rondas'] as $r)
                                            <td class="p-3 text-center">
                                                @if ($r['global'] !== null)
                                                    <div class="font-bold text-slate-900">{{ $r['global'] }}</div>
                                                    <div class="text-[10px] text-slate-500 font-medium">
                                                        ({{ $r['individual'] }})
                                                    </div>
                                                @else
                                                    <span class="text-slate-200">-</span>
                                                @endif
                                            </td>
                                        @endforeach

                                        <td class="p-4 text-center font-black text-xl"
                                            style="color: {{ $currentStyle['totalText'] }}">
                                            {{ $row['total_global'] }}
                                        </td>

                                        <td class="p-4 text-center font-bold text-slate-500">
                                            {{ $row['total_individual'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        </div>

        {{-- SECCIÓN INFERIOR: EMPAREJAMIENTOS (CON DISEÑO ORIGINAL) --}}
        <section x-data="{ open: false }" class="w-full">
            <div @click="open = !open"
                class="flex items-center justify-between cursor-pointer group bg-slate-900 p-6 rounded-2xl shadow-xl transition-all border-l-8 border-[#c5a059] hover:bg-slate-800">
                <div class="flex items-center gap-4">
                    <div class="h-10 w-3 bg-[#c5a059] rounded-full shadow-[0_0_15px_rgba(197,160,89,0.4)]"></div>
                    <div>
                        <h2 class="text-2xl font-black text-white uppercase tracking-tight">Rondas del Torneo</h2>
                        <p class="text-sm text-slate-400 font-medium italic">Selecciona una ronda para ver los duelos
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-[10px] font-black text-[#c5a059] uppercase tracking-widest"
                        x-text="open ? 'Ocultar' : 'Ver Duelos'"></span>
                    <div class="bg-slate-800 p-2 rounded-full text-white transition-transform duration-300"
                        :class="open ? 'rotate-180' : ''">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform -translate-y-4"
                x-transition:enter-end="opacity-100 transform translate-y-0" class="mt-6">
                <div class="relative p-1 bg-slate-100 rounded-3xl border border-slate-200 overflow-hidden">
                    {{-- Fondo de tablero decorativo --}}
                    <div class="absolute inset-0 opacity-[0.03]"
                        style="background-image: conic-gradient(#000 0.25turn, #fff 0.25turn 0.5turn, #000 0.5turn 0.75turn, #fff 0.75turn); background-size: 40px 40px;">
                    </div>

                    <div class="relative z-10 flex flex-wrap justify-center gap-6 p-10">
                        @foreach ($torneo->rondas->sortBy('numero') as $ronda)
                            <button wire:click="verRonda({{ $ronda->id }}, {{ $ronda->numero }})"
                                class="group relative flex flex-col items-center justify-center w-32 h-32 bg-white rounded-2xl shadow-sm border-b-4 border-slate-300 transition-all duration-300 hover:-translate-y-2 hover:border-[#c5a059] hover:shadow-xl active:scale-95 overflow-hidden">

                                <span
                                    class="absolute top-2 right-3 text-[10px] font-black text-slate-300 group-hover:text-[#c5a059]/30 transition-colors">#0{{ $ronda->numero }}</span>

                                <div class="mb-1 text-2xl group-hover:scale-125 transition-transform duration-300">
                                    <span class="text-slate-400 group-hover:text-[#c5a059]">⏲</span>
                                </div>

                                <span
                                    class="text-xs uppercase font-bold text-slate-400 group-hover:text-slate-500 tracking-tighter">Fase
                                    de Grupo</span>
                                <span class="text-xl font-black text-slate-700 group-hover:text-slate-900">Ronda
                                    {{ $ronda->numero }}</span>

                                <div
                                    class="absolute bottom-0 left-0 w-0 h-1 bg-[#c5a059] group-hover:w-full transition-all duration-500">
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    </main>

    <style>
        [x-cloak] {
            display: none !important;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #c5a059;
        }
    </style>


    {{-- MODAL ESTILO PREMIUM --}}
    @if ($openModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            {{-- Backdrop con blur --}}
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="cerrarModal"></div>

            <div
                class="relative bg-white w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col border-t-8 border-[#c5a059]">

                {{-- Modal Header --}}
                <div class="p-6 border-b flex justify-between items-center bg-slate-50">
                    <div>
                        <h2 class="text-2xl font-black text-slate-800 uppercase italic">Enfrentamientos</h2>
                        <p class="text-[#c5a059] font-bold">Ronda {{ $rondaSeleccionada }}</p>
                    </div>
                    <button wire:click="cerrarModal"
                        class="p-2 hover:bg-red-50 text-slate-400 hover:text-red-500 rounded-full transition-colors text-2xl">
                        ✕
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-6 overflow-y-auto bg-white">
                    @foreach ($emparejamientos->groupBy('estacion') as $estacion => $partidas)
                        @php
                            $primeraPartida = $partidas->first();
                            $equipo1Nombre = $primeraPartida->jugadorBlancas->equipo->nombre ?? 'Equipo A';
                            $equipo2Nombre = $primeraPartida->jugadorNegras->equipo->nombre ?? 'Equipo B';
                            $equipo1Id = $primeraPartida->jugadorBlancas->equipo_id;
                            $equipo2Id = $primeraPartida->jugadorNegras->equipo_id;
                        @endphp

                        <div class="mb-10 last:mb-0">
                            {{-- Header de la Estación --}}
                            <div class="flex items-center justify-between mb-4 bg-slate-900 p-3 rounded-lg shadow-md">
                                <span class="text-white font-bold px-3 py-1 bg-[#c5a059] rounded text-sm uppercase">
                                    Estación {{ $estacion }}
                                </span>
                                <span class="text-[#c5a059] font-bold md:text-lg italic">
                                    {{ $equipo1Nombre }} <span class="text-white mx-2">VS</span> {{ $equipo2Nombre }}
                                </span>
                            </div>

                            {{-- Lista de Partidas --}}
                            <div class="grid grid-cols-1 gap-3">
                                @foreach ($partidas as $emp)
                                    <div
                                        class="grid grid-cols-1 md:grid-cols-7 items-center bg-slate-50 border border-slate-200 rounded-xl p-4 hover:bg-[#c5a059]/5 transition-colors group">

                                        {{-- BLANCAS --}}
                                        <div class="md:col-span-3 flex items-center gap-4">
                                            <div
                                                class="w-10 h-10 flex-shrink-0 bg-white border-2 border-slate-300 rounded-full flex items-center justify-center shadow-sm">
                                                <span class="text-xl">♔</span>
                                            </div>
                                            <div class="flex flex-col">
                                                <span
                                                    class="font-bold text-slate-800">{{ $emp->jugadorBlancas?->nombre }}</span>
                                                <span class="text-[10px] uppercase font-bold text-slate-400">Tablero
                                                    {{ $emp->jugadorBlancas?->tablero }}</span>
                                                <span
                                                    class="text-[11px] text-[#c5a059] font-semibold">{{ $equipo1Nombre }}</span>
                                            </div>
                                        </div>

                                        {{-- MARCADOR CENTRAL --}}
                                        <div class="md:col-span-1 flex flex-col items-center justify-center gap-1">
                                            <span
                                                class="bg-slate-200 text-slate-500 text-[10px] font-black px-2 py-0.5 rounded-full uppercase">
                                                Mesa {{ $emp->mesa }}
                                            </span>

                                            {{-- Marcador Dinámico --}}
                                            <div
                                                class="text-lg font-black text-slate-800 bg-white border border-slate-200 px-3 py-1 rounded-lg shadow-sm">
                                                {{ $emp->resultado ?: '0 - 0' }}
                                            </div>

                                            @if (!$emp->resultado)
                                                <span class="text-[9px] text-slate-400 font-bold uppercase italic">Por
                                                    definirse</span>
                                            @endif
                                        </div>

                                        {{-- NEGRAS --}}
                                        <div class="md:col-span-3 flex items-center justify-end gap-4 text-right">
                                            <div class="flex flex-col">
                                                <span
                                                    class="font-bold text-slate-800">{{ $emp->jugadorNegras?->nombre }}</span>
                                                <span class="text-[10px] uppercase font-bold text-slate-400">Tablero
                                                    {{ $emp->jugadorNegras?->tablero }}</span>
                                                <span
                                                    class="text-[11px] text-[#c5a059] font-semibold">{{ $equipo2Nombre }}</span>
                                            </div>
                                            <div
                                                class="w-10 h-10 flex-shrink-0 bg-slate-800 border-2 border-slate-900 rounded-full flex items-center justify-center shadow-sm">
                                                <span class="text-xl text-white">♚</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- SECCIÓN DE PUNTUACIÓN GRUPAL --}}
                            @php
                                $resEquipo1 = ResultadoEquipo::where('ronda_id', $primeraPartida->ronda_id)
                                    ->where('equipo_id', $equipo1Id)
                                    ->first();
                                $resEquipo2 = ResultadoEquipo::where('ronda_id', $primeraPartida->ronda_id)
                                    ->where('equipo_id', $equipo2Id)
                                    ->first();
                            @endphp

                            <div
                                class="mt-6 relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50 shadow-inner">
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-[#c5a059]"></div>
                                <div class="p-4">
                                    <h4
                                        class="text-[10px] uppercase font-black text-slate-400 tracking-[0.2em] mb-3 flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-[#c5a059]"></span>
                                        Resumen de Puntuación
                                    </h4>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        {{-- Card Equipo 1 --}}
                                        <div
                                            class="flex items-center justify-between p-3 bg-white rounded-lg border border-slate-100 shadow-sm transition-colors">
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">Equipo</span>
                                                <span
                                                    class="font-black text-slate-700 leading-none">{{ $equipo1Nombre }}</span>
                                            </div>
                                            <div class="flex gap-4">
                                                <div class="flex flex-col items-end border-r border-slate-100 pr-4">
                                                    <span
                                                        class="text-[9px] text-slate-400 font-bold uppercase">Indiv.</span>
                                                    <span
                                                        class="text-sm font-bold text-slate-600">{{ $resEquipo1->puntos_individuales ?? '0.0' }}
                                                        <span class="text-[10px]">pts</span></span>
                                                </div>
                                                <div class="flex flex-col items-end">
                                                    <span
                                                        class="text-[9px] text-[#c5a059] font-black uppercase">Global</span>
                                                    <span
                                                        class="text-lg font-black text-slate-900 leading-none">{{ $resEquipo1->puntos_globales ?? '0' }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Card Equipo 2 --}}
                                        <div
                                            class="flex items-center justify-between p-3 bg-white rounded-lg border border-slate-100 shadow-sm transition-colors">
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">Equipo</span>
                                                <span
                                                    class="font-black text-slate-700 leading-none">{{ $equipo2Nombre }}</span>
                                            </div>
                                            <div class="flex gap-4">
                                                <div class="flex flex-col items-end border-r border-slate-100 pr-4">
                                                    <span
                                                        class="text-[9px] text-slate-400 font-bold uppercase">Indiv.</span>
                                                    <span
                                                        class="text-sm font-bold text-slate-600">{{ $resEquipo2->puntos_individuales ?? '0.0' }}
                                                        <span class="text-[10px]">pts</span></span>
                                                </div>
                                                <div class="flex flex-col items-end">
                                                    <span
                                                        class="text-[9px] text-[#c5a059] font-black uppercase">Global</span>
                                                    <span
                                                        class="text-lg font-black text-slate-900 leading-none">{{ $resEquipo2->puntos_globales ?? '0' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif




</div>

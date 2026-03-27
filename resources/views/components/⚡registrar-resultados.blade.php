<?php

use Livewire\Component;
use App\Models\Torneo;
use App\Models\Emparejamiento;
use App\Models\Equipo;
use App\Models\ResultadoEquipo;
use App\Models\Jugador;

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

    // --- NUEVAS PROPIEDADES PARA PERFIL ---
    public $editandoPerfilId = null;
    public $nuevaEdad;
    public $nuevoGenero;
    public $nuevoElo;

    public $mostrarGanadores = false;

    

    public function mount()
    {
        $this->torneo = Torneo::with('rondas')->find(1);

        // cargar equipos con jugadores
        $this->equipos = Equipo::with('jugadores')->get();
        $this->mostrarGanadores = $this->torneo->mostrar_ganadores ?? false;
    }

    public function definirMejores()
    {
        // Aquí podrías guardar en la BD que el torneo terminó
        $this->torneo->update(['mostrar_ganadores' => true]);
        $this->mostrarGanadores = true;

        session()->flash('message', '¡Ranking final definido correctamente!');
    }

    public function limpiarGanadores()
    {
        // Revertimos en la base de datos
        $this->torneo->update(['mostrar_ganadores' => false]);
        $this->mostrarGanadores = false;

        session()->flash('message', 'Se ha ocultado el ranking de mejores jugadores.');
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
        // Ordenar igual que siempre
        $data = $this->tablaGeneral
            ->values()
            ->sortByDesc(function ($row) {
                return [$row['total_global'], $row['total_individual']];
            })
            ->values();

        $ranking = [];
        $posActual = 1;
        $count = 0;
        $lastKey = null;

        // 🔥 GENERAR RANKING CON EMPATES
        foreach ($data as $i => $row) {
            $key = ($row['total_global'] ?? 0) . '-' . ($row['total_individual'] ?? 0);

            if ($key !== $lastKey) {
                $posActual = $count + 1;
            }

            $ranking[$i] = $posActual;

            $lastKey = $key;
            $count++;
        }

        // 🔥 MAPEAR DATOS
        return $data->map(function ($row, $index) use ($ranking) {
            $nombre = $row['equipo']->nombre;
            $nombreLower = strtolower($nombre);
            $posicion = $ranking[$index];

            $global = $row['total_global'] ?? 0;
            $individual = $row['total_individual'] ?? 0;

            $tienePuntos = $global > 0 || $individual > 0;

            // 🔥 MEDALLAS CON EMPATE REAL
            if ($tienePuntos) {
                $prefijo = match ($posicion) {
                    1 => '🥇 ',
                    2 => '🥈 ',
                    3 => '🥉 ',
                    default => "#$posicion ",
                };
            } else {
                $prefijo = "#$posicion ";
            }

            // 🎨 COLORES (igual que tenías)
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
                'global' => $global,
                'individual' => $individual,
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

    // EDICIÓN PARA EDAD Y GENERO

    public function editarPerfil($id)
    {
        $jugador = Jugador::find($id);
        $this->editandoPerfilId = $id;
        $this->nuevaEdad = $jugador->edad;
        $this->nuevoGenero = $jugador->genero;
        $this->nuevoElo = $jugador->elo;
        $this->editandoJugadorId = null;
    }

    public function cancelarPerfil()
    {
        $this->editandoPerfilId = null;
    }

    public function guardarPerfil()
    {
        $this->validate([
            'nuevaEdad' => 'nullable|integer|min:5|max:100',
            'nuevoGenero' => 'nullable|string',
            'nuevoElo' => 'nullable|integer|min:0|max:3500', // <--- Validar ELO
        ]);

        $jugador = Jugador::find($this->editandoPerfilId);
        $jugador->update([
            'edad' => $this->nuevaEdad,
            'genero' => $this->nuevoGenero,
            'elo' => $this->nuevoElo, // <--- Guardar ELO
        ]);

        $this->equipos = Equipo::with('jugadores')->get();
        $this->cancelarPerfil();
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
                Registro de resultados torneo por equipos
            </h1>
            <div class="w-24 h-1 bg-white mx-auto mt-4 rounded-full"></div>
        </div>
    </header>





    <main class="max-w-7xl mx-auto px-4">



        {{-- TABLA DE POSICIONES --}}

        {{-- TABLA DE POSICIONES PRO --}}


        <section class="mb-16 w-full px-4 md:px-6" x-data="{ open: false }">
            @php
                $tabla = $this->tablaGeneral->values();

                if (!function_exists('getMedallaStyleById')) {
                    function getMedallaStyleById($pos, $tienePuntos)
                    {
                        // Si no hay puntos, devolvemos estilo neutro
                        if (!$tienePuntos) {
                            return [
                                'bg' => 'transparent',
                                'border' => 'transparent',
                                'totalText' => '#64748b', // slate-500
                            ];
                        }

                        return match ($pos) {
                            1 => [
                                'bg' => 'rgba(255, 236, 179, 0.45)',
                                'border' => '#FFD700',
                                'totalText' => '#D4AF37',
                            ],
                            2 => [
                                'bg' => 'rgba(238, 238, 238, 0.45)',
                                'border' => '#C0C0C0',
                                'totalText' => '#999999',
                            ],
                            3 => [
                                'bg' => 'rgba(237, 219, 203, 0.45)',
                                'border' => '#CD7F32',
                                'totalText' => '#A5682A',
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

            <section class="mb-16 w-full px-4 md:px-6" x-data="{ open: false }">

                @php
                    $tabla = $this->tablaGeneral->sortByDesc('total_global')->sortByDesc('total_individual')->values();

                    // 🔥 GENERAR RANKING CON EMPATES
                    $ranking = [];
                    $posActual = 1;
                    $count = 0;
                    $lastKey = null;

                    foreach ($tabla as $i => $row) {
                        $key = $row['total_global'] . '-' . $row['total_individual'];

                        if ($key !== $lastKey) {
                            $posActual = $count + 1;
                        }

                        $ranking[$i] = $posActual;

                        $lastKey = $key;
                        $count++;
                    }

                    if (!function_exists('getMedallaStyleById')) {
                        function getMedallaStyleById($pos, $tienePuntos)
                        {
                            if (!$tienePuntos) {
                                return [
                                    'bg' => 'transparent',
                                    'border' => 'transparent',
                                    'totalText' => '#94a3b8',
                                ];
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

                <!-- HEADER -->
                <div @click="open = !open"
                    class="flex items-center justify-between cursor-pointer group bg-slate-900 p-6 rounded-2xl shadow-xl mb-6 border-l-8 border-[#c5a059]">

                    <div class="flex items-center gap-4">
                        <span class="text-2xl text-[#c5a059]">♖</span>
                        <h2 class="text-2xl font-bold text-white uppercase tracking-tight">
                            Tabla de Posiciones
                        </h2>
                    </div>

                    <span class="text-xs font-bold text-[#c5a059]" x-text="open ? 'Ocultar' : 'Mostrar'"></span>
                </div>

                <!-- TABLA -->
                <div x-show="open" x-cloak x-transition class="w-full">
                    <div class="w-full overflow-hidden rounded-2xl shadow-xl border border-slate-200 bg-white">
                        <div class="w-full overflow-x-auto">

                            <table class="w-full min-w-[800px] md:min-w-[1100px] text-sm text-center border-collapse">

                                <!-- HEAD -->
                                <thead class="bg-slate-900 text-white">
                                    <tr>
                                        <th class="p-4">#</th>
                                        <th class="p-4 text-left">Equipo</th>

                                        @foreach ($torneo->rondas as $ronda)
                                            <th class="p-4">R{{ $ronda->numero }}</th>
                                        @endforeach

                                        <th class="p-4">Global</th>
                                        <th class="p-4">Individual</th>
                                    </tr>
                                </thead>

                                <!-- BODY -->
                                <tbody>
                                    @foreach ($tabla as $index => $row)
                                        @php
                                            $pos = $ranking[$index]; // 🔥 posición real con empates
                                            $tienePuntos = $row['total_global'] > 0;

                                            // 🔥 SOLO 3 FILAS CON MEDALLA
                                            $tieneMedalla = $pos <= 3;

                                            $currentStyle = getMedallaStyleById($tieneMedalla ? $pos : 0, $tienePuntos);

                                            $rowInlineStyle = "background-color: {$currentStyle['bg']} !important; border-left: 8px solid {$currentStyle['border']};";
                                        @endphp

                                        <tr style="{{ $rowInlineStyle }}"
                                            class="border-b border-slate-100 transition hover:bg-white/50">

                                            <!-- POSICIÓN -->
                                            <td class="p-4 font-black text-xl">
                                                @if ($tienePuntos && $tieneMedalla)
                                                    {{ $pos == 1 ? '🥇' : ($pos == 2 ? '🥈' : '🥉') }}
                                                @else
                                                    <span style="color: #cbd5e1;">{{ $pos }}</span>
                                                @endif
                                            </td>

                                            <!-- EQUIPO -->
                                            <td class="p-4 text-left font-bold text-slate-800 whitespace-nowrap">
                                                {{ $row['equipo']->nombre }}
                                            </td>

                                            <!-- RONDAS -->
                                            @foreach ($row['rondas'] as $r)
                                                <td class="p-3">
                                                    @if ($r['global'] !== null)
                                                        <div class="font-bold text-slate-900">
                                                            {{ $r['global'] }}
                                                        </div>
                                                        <div class="text-[10px] text-slate-500 font-medium">
                                                            ({{ $r['individual'] }})
                                                        </div>
                                                    @else
                                                        <span style="color: #e2e8f0;">-</span>
                                                    @endif
                                                </td>
                                            @endforeach

                                            <!-- TOTAL GLOBAL -->
                                            <td class="p-4 font-black text-xl"
                                                style="color: {{ $currentStyle['totalText'] }}">
                                                {{ $row['total_global'] }}
                                            </td>

                                            <!-- TOTAL INDIVIDUAL -->
                                            <td class="p-4 font-bold text-slate-700">
                                                {{ $row['total_individual'] }}
                                            </td>

                                        </tr>
                                    @endforeach
                                </tbody>

                            </table>

                        </div>
                    </div>
                </div>
            </section>





            <section x-data="{
                open: false,
                chart: null,
                loading: false,
                puntosIndividual: [],
                ranking: [],
            
                init() {
                    this.$watch('open', value => {
                        if (value) {
                            this.$nextTick(() => {
                                this.renderOrUpdate();
                            });
                        }
                    });
            
                    document.addEventListener('livewire:initialized', () => {
                        @this.on('graficaActualizada', () => {
                            if (this.open) this.renderOrUpdate();
                        });
                    });
                },
            
                async refreshManual() {
                    this.loading = true;
                    let freshData = await @this.getGraficaData();
                    this.renderOrUpdate(freshData);
                    setTimeout(() => { this.loading = false; }, 600);
                },
            
                generarRanking(data) {
                    let ranking = [];
                    let posActual = 1;
                    let count = 0;
                    let lastKey = null;
            
                    data.forEach((e, i) => {
                        let key = e.global + '-' + e.individual;
            
                        if (key !== lastKey) {
                            posActual = count + 1;
                        }
            
                        ranking[i] = posActual;
            
                        lastKey = key;
                        count++;
                    });
            
                    return ranking;
                },
            
                renderOrUpdate(incomingData = null) {
                    let data = incomingData ? incomingData : @js($this->graficaEquipos);
            
                    if (!data || data.length === 0) return;
            
                    // 🔥 ORDENAR IGUAL QUE BACKEND
                    data = [...data].sort((a, b) => {
                        if (b.global !== a.global) return b.global - a.global;
                        return b.individual - a.individual;
                    });
            
                    // 🔥 RANKING CON EMPATES
                    this.ranking = this.generarRanking(data);
            
                    // 🔥 NOMBRES CON MEDALLAS CORRECTAS
                    let nombres = data.map((e, i) => {
                        let pos = this.ranking[i];
                        let tienePuntos = e.global > 0 || e.individual > 0;
            
                        let prefijo = '';
            
                        if (tienePuntos) {
                            if (pos === 1) prefijo = '🥇 ';
                            else if (pos === 2) prefijo = '🥈 ';
                            else if (pos === 3) prefijo = '🥉 ';
                            else prefijo = `#${pos} `;
                        } else {
                            prefijo = `#${pos} `;
                        }
            
                        // 🔥 LIMPIAR PREFIJOS PREVIOS (por si vienen del backend)
                        let nombreLimpio = e.nombre.replace(/^(🥇|🥈|🥉|#\d+\s)/, '');
            
                        return prefijo + nombreLimpio;
                    });
            
                    let puntosGlobal = data.map(e => Number(e.global));
                    this.puntosIndividual = data.map(e => Number(e.individual));
                    let colores = data.map(e => e.color);
            
                    if (this.chart) {
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
                                dataLabels: {
                                    position: 'bottom',
                                    hideOverflowingText: false
                                }
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            textAnchor: 'start',
                            style: {
                                colors: ['#fff'],
                                fontWeight: '800',
                                fontSize: '10px'
                            },
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
            }" class="mb-16 w-full px-6">

                <div @click="open = !open"
                    class="flex items-center justify-between cursor-pointer group bg-slate-900 p-6 rounded-2xl shadow-xl transition-all mb-6 border-l-8 border-[#c5a059] hover:bg-slate-800">

                    <div class="flex items-center gap-4">
                        <span class="text-2xl text-[#c5a059] group-hover:rotate-12 transition-transform">♜</span>
                        <h2 class="text-2xl font-bold text-white uppercase tracking-tight">Rendimiento de Equipos</h2>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-[10px] font-black text-[#c5a059] uppercase tracking-widest"
                            x-text="open ? 'Ocultar' : 'Ver Clasificación'"></span>
                        <div class="bg-slate-800 p-1.5 rounded-full text-white transition-transform duration-300"
                            :class="open ? 'rotate-180' : ''">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div x-show="open" x-cloak x-transition
                    class="bg-white p-6 rounded-2xl shadow-xl border border-slate-200 relative">

                    <div class="absolute top-4 right-4 z-10">
                        <button @click="refreshManual()"
                            class="flex items-center gap-2 px-3 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-wider rounded-xl transition-all border border-slate-200 shadow-sm"
                            :disabled="loading">
                            <svg :class="loading ? 'animate-spin' : ''" class="w-3.5 h-3.5 text-[#c5a059]"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                </path>
                            </svg>
                            <span x-text="loading ? 'Cargando...' : 'Actualizar Datos'"></span>
                        </button>
                    </div>

                    <div x-ref="mapaEquipos" wire:ignore></div>
                </div>
            </section>





            <section class="mb-16 w-full px-6" x-data="{ open: false }">
                <div @click="open = !open"
                    class="flex items-center justify-between cursor-pointer group bg-slate-900 p-6 rounded-2xl shadow-xl mb-6 border-l-8 border-[#c5a059]">
                    <div class="flex items-center gap-4">
                        <span class="text-2xl text-[#c5a059]">♞</span>
                        <h2 class="text-2xl font-bold text-white uppercase tracking-tight">
                            Ranking Individual por Género
                        </h2>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="flex gap-2">
                            @if (!$mostrarGanadores)
                                <button wire:click.stop="definirMejores"
                                    class="bg-green-600 hover:bg-green-700 text-white text-[10px] font-black px-4 py-2 rounded-lg transition-all active:scale-95 uppercase shadow-lg flex items-center gap-1">
                                    <span>🏆</span> Definir Ganadores
                                </button>
                            @else
                                <button wire:click.stop="limpiarGanadores"
                                    class="bg-red-600 hover:bg-red-700 text-white text-[10px] font-black px-4 py-2 rounded-lg transition-all active:scale-95 uppercase shadow-lg flex items-center gap-1">
                                    <span>🔄</span> Limpiar / Corregir
                                </button>
                            @endif
                        </div>

                        <div class="h-6 w-[1px] bg-slate-700 mx-2"></div>

                        <span class="text-xs font-bold text-[#c5a059]" x-text="open ? 'Ocultar' : 'Mostrar'"></span>
                    </div>
                </div>

                <div x-show="open" x-cloak x-transition>
                    @php
                        $categorias = [
                            [
                                'titulo' => 'Categoría Masculina',
                                'genero' => 'Masculino',
                                'icon' => '♂️',
                                'label' => 'Mejor jugador masculino',
                            ],
                            [
                                'titulo' => 'Categoría Femenina',
                                'genero' => 'Femenino',
                                'icon' => '♀️',
                                'label' => 'Mejor jugadora femenina',
                            ],
                        ];

                        // 🔥 MAPA REAL DE POSICIONES DE EQUIPOS
                        $rankingEquipos = $this->tablaGeneral->values()->mapWithKeys(
                            fn($item, $index) => [
                                $item['equipo']->id => $index + 1,
                            ],
                        );
                    @endphp

                    @foreach ($categorias as $cat)
                        @php
                            // 🔥 ORDEN CORRECTO
                            $rankingFiltrado = $this->tablaIndividual
                                ->filter(fn($item) => $item['jugador']->genero === $cat['genero'])
                                ->sortBy(fn($i) => $rankingEquipos[$i['equipo']->id] ?? 9999) // 2️⃣ equipo mejor pos gana
                                ->sortByDesc(fn($i) => $i['total']) // 1️⃣ puntos jugador
                                ->values();

                            $mejorJugador = $rankingFiltrado->first();
                        @endphp

                        <div class="mb-10">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="text-xl">{{ $cat['icon'] }}</span>
                                <h3 class="text-lg font-black text-slate-700 uppercase">{{ $cat['titulo'] }}</h3>
                            </div>

                            <div class="overflow-x-auto bg-white rounded-2xl shadow-xl border">
                                <table class="min-w-full text-sm text-center">
                                    <thead class="bg-slate-900 text-white">
                                        <tr>
                                            <th class="p-4">#</th>
                                            <th class="p-4 text-left">Jugador</th>
                                            <th class="p-4 text-left">Equipo</th>
                                            @foreach ($torneo->rondas as $ronda)
                                                <th class="p-4">R{{ $ronda->numero }}</th>
                                            @endforeach
                                            <th class="p-4">Total</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($rankingFiltrado as $index => $row)
                                            @php
                                                $pos = $index + 1;
                                                $tienePuntos = $row['total'] > 0;
                                                $currentStyle = getMedallaStyleById($pos, $tienePuntos);
                                            @endphp

                                            <tr style="background-color: {{ $currentStyle['bg'] }}; border-left: 8px solid {{ $currentStyle['border'] }};"
                                                class="border-b">

                                                <td class="p-4 font-black text-xl">
                                                    {{ $tienePuntos ? ($pos == 1 ? '🥇' : ($pos == 2 ? '🥈' : ($pos == 3 ? '🥉' : $pos))) : $pos }}
                                                </td>

                                                <td class="p-4 text-left font-bold">
                                                    {{ $row['jugador']->nombre }}
                                                </td>

                                                <td class="p-4 text-left text-slate-600">
                                                    {{ $row['equipo']->nombre }}
                                                </td>

                                                @foreach ($row['porRonda'] as $p)
                                                    <td class="p-3 font-bold">{{ $p ?? '-' }}</td>
                                                @endforeach

                                                <td class="p-4 font-black text-xl"
                                                    style="color: {{ $currentStyle['totalText'] }}">
                                                    {{ $row['total'] }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- GANADOR --}}
                            @if ($mostrarGanadores && $mejorJugador && $mejorJugador['total'] > 0)
                                <div class="mt-4 px-4 py-3 bg-green-50 border-l-4 border-green-500 rounded shadow">
                                    <p class="text-green-700 font-black uppercase text-sm flex items-center gap-2">
                                        🏆 {{ $cat['label'] }}:
                                        <span class="text-green-800 text-lg">
                                            {{ $mejorJugador['jugador']->nombre }}
                                        </span>

                                        {{-- <span class="bg-green-600 text-white px-2 py-0.5 rounded text-xs">
                                            {{ $mejorJugador['total'] }} PTS
                                        </span>

                                        <span class="text-[10px] text-green-600">
                                            (Desempate por posición de equipo)
                                        </span> --}}
                                    </p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="mb-16 w-full px-6" x-data="{ open: false }">
                <div @click="open = !open"
                    class="flex items-center justify-between cursor-pointer group bg-slate-900 p-6 rounded-2xl shadow-xl transition-all mb-6 border-l-8 border-[#c5a059]">

                    <div class="flex items-center gap-4">
                        <span class="text-2xl text-[#c5a059] group-hover:animate-bounce-slow">♜</span>
                        <h2 class="text-2xl font-bold text-white uppercase tracking-tight">
                            Administrar Equipos
                        </h2>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-[10px] font-black text-[#c5a059] uppercase tracking-widest hidden md:inline"
                            x-text="open ? 'Colapsar' : 'Desplegar Lista'"></span>

                        <div class="bg-slate-800 p-1.5 rounded-full text-white transition-transform duration-300"
                            :class="open ? 'rotate-180' : ''">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M19 9l-7 7-7-7">
                                </path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform -translate-y-4"
                    x-transition:enter-end="opacity-100 transform translate-y-0">

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        @foreach ($equipos->sortBy('nombre') as $equipo)
                            @php
                                $nombreLower = strtolower($equipo->nombre);
                                $colorClase = match (true) {
                                    str_contains($nombreLower, 'campeones') => [
                                        'border' => 'border-red-500',
                                        'text' => 'text-red-600',
                                    ],
                                    str_contains($nombreLower, 'bloops') => [
                                        'border' => 'border-white',
                                        'text' => 'text-white',
                                    ],
                                    str_contains($nombreLower, 'apertura maestra') => [
                                        'border' => 'border-gray-400',
                                        'text' => 'text-gray-400',
                                    ],
                                    str_contains($nombreLower, 'gambitos') && !str_contains($nombreLower, 'dama') => [
                                        'border' => 'border-green-500',
                                        'text' => 'text-green-600',
                                    ],
                                    str_contains($nombreLower, 'gambito de dama') => [
                                        'border' => 'border-blue-500',
                                        'text' => 'text-blue-600',
                                    ],
                                    str_contains($nombreLower, 'changos') => [
                                        'border' => 'border-pink-400',
                                        'text' => 'text-pink-500',
                                    ],
                                    default => ['border' => 'border-[#c5a059]', 'text' => 'text-[#c5a059]'],
                                };
                                $isEditing = $editandoEquipoId === $equipo->id;
                            @endphp

                            <div
                                class="group relative bg-white border-t-4 {{ $colorClase['border'] }} rounded-xl shadow-md hover:shadow-2xl transition-all duration-300 overflow-hidden">
                                <div
                                    class="bg-slate-900 p-5 text-center relative z-10 border-b border-slate-800 min-h-[90px] flex items-center justify-center">
                                    @if ($isEditing)
                                        <div class="flex flex-col gap-2 w-full">
                                            <input type="text" wire:model.defer="nuevoNombre"
                                                class="w-full bg-slate-800 border-slate-700 text-white text-center font-bold rounded-lg focus:ring-1 focus:ring-[#c5a059] focus:border-[#c5a059] uppercase tracking-widest text-sm py-1"
                                                autofocus wire:keydown.enter="actualizarNombreEquipo"
                                                wire:keydown.escape="cancelarEdicion">
                                            <div class="flex justify-center gap-2">
                                                <button wire:click="actualizarNombreEquipo"
                                                    class="text-[9px] bg-green-600 text-white px-2 py-0.5 rounded hover:bg-green-700 uppercase font-black">OK</button>
                                                <button wire:click="cancelarEdicion"
                                                    class="text-[9px] bg-slate-700 text-white px-2 py-0.5 rounded hover:bg-slate-600 uppercase font-black">X</button>
                                            </div>
                                        </div>
                                    @else
                                        <h3
                                            class="font-black text-xl {{ $colorClase['text'] }} uppercase tracking-widest group-hover:scale-105 transition-transform">
                                            {{ $equipo->nombre }}
                                        </h3>
                                        <button
                                            wire:click="editarEquipo({{ $equipo->id }}, '{{ $equipo->nombre }}')"
                                            class="absolute top-3 right-3 opacity-100 text-slate-600 hover:text-[#c5a059] transition-colors p-1"
                                            title="Editar nombre del equipo">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>

                                <div class="p-6">



                                    <ul class="space-y-3" style="list-style: none; padding: 0;">
                                        @foreach ($equipo->jugadores->sortBy('tablero') as $jugador)
                                            <li
                                                class="flex flex-col border-b border-slate-100 pb-2 last:border-0 group/item">
                                                <div class="flex items-center gap-3 w-full">
                                                    <span
                                                        class="flex-shrink-0 {{ str_contains($colorClase['text'], 'white') ? 'text-slate-400' : $colorClase['text'] }} text-lg">♟</span>

                                                    <span
                                                        class="flex-shrink-0 ml-1 text-[9px] font-black bg-slate-900 text-white px-1.5 py-0.5 rounded shadow-sm">
                                                        {{ $jugador->tablero }}
                                                    </span>

                                                    @if ($editandoJugadorId === $jugador->id)
                                                        {{-- MODO EDICIÓN NOMBRE --}}
                                                        <div
                                                            class="flex flex-col gap-2 w-full bg-white p-1 rounded-lg border border-slate-200 shadow-sm">
                                                            <div class="flex flex-col gap-1">
                                                                <label
                                                                    style="font-size: 9px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-left: 2px;">Nombre
                                                                    del Jugador</label>
                                                                <input type="text"
                                                                    wire:model.defer="nuevoNombreJugador"
                                                                    style="width: 100%; border: 1px solid #cbd5e1; border-radius: 4px; padding: 4px 8px; font-weight: bold; font-size: 14px; outline: none;"
                                                                    autofocus
                                                                    wire:keydown.enter="actualizarNombreJugador"
                                                                    wire:keydown.escape="cancelarEdicionJugador">
                                                            </div>
                                                            <div class="flex justify-end gap-1">
                                                                <button wire:click="actualizarNombreJugador"
                                                                    style="background-color: #16a34a; color: white; padding: 4px 10px; border-radius: 4px; font-size: 10px; font-weight: 900; border: none; cursor: pointer; text-transform: uppercase;">OK</button>
                                                                <button wire:click="cancelarEdicionJugador"
                                                                    style="background-color: #dc2626; color: white; padding: 4px 10px; border-radius: 4px; font-size: 10px; font-weight: 900; border: none; cursor: pointer; text-transform: uppercase;">X</button>
                                                            </div>
                                                        </div>
                                                    @elseif($editandoPerfilId === $jugador->id)
                                                        {{-- MODO EDICIÓN PERFIL (EDAD, GÉNERO, ELO) --}}
                                                        <div
                                                            class="flex flex-col gap-2 w-full bg-slate-50 p-2 rounded-lg border border-blue-200 shadow-inner">
                                                            <div class="flex gap-2">
                                                                {{-- Columna Edad --}}
                                                                <div class="flex flex-col gap-1" style="width: 25%;">
                                                                    <label
                                                                        style="font-size: 8px; font-weight: 900; color: #3b82f6; text-transform: uppercase; margin-left: 2px;">Edad</label>
                                                                    <input type="number" placeholder="00"
                                                                        wire:model.defer="nuevaEdad"
                                                                        style="width: 100%; border: 1px solid #cbd5e1; border-radius: 4px; padding: 4px; font-size: 11px;">
                                                                </div>

                                                                {{-- Columna Género --}}
                                                                <div class="flex flex-col gap-1" style="width: 40%;">
                                                                    <label
                                                                        style="font-size: 8px; font-weight: 900; color: #3b82f6; text-transform: uppercase; margin-left: 2px;">Género</label>
                                                                    <select wire:model.defer="nuevoGenero"
                                                                        style="width: 100%; border: 1px solid #cbd5e1; border-radius: 4px; padding: 4px; font-size: 11px; background-color: white;">
                                                                        <option value="">Seleccionar</option>
                                                                        <option value="Masculino">Masculino</option>
                                                                        <option value="Femenino">Femenino</option>
                                                                    </select>
                                                                </div>

                                                                {{-- Columna ELO --}}
                                                                <div class="flex flex-col gap-1" style="width: 35%;">
                                                                    <label
                                                                        style="font-size: 8px; font-weight: 900; color: #3b82f6; text-transform: uppercase; margin-left: 2px;">Elo
                                                                        Rating</label>
                                                                    <input type="number" placeholder="ELO"
                                                                        wire:model.defer="nuevoElo"
                                                                        style="width: 100%; border: 1px solid #cbd5e1; border-radius: 4px; padding: 4px; font-size: 11px; font-weight: bold; color: #1e293b;">
                                                                </div>
                                                            </div>
                                                            <div class="flex justify-end gap-1 mt-1">
                                                                <button wire:click="guardarPerfil"
                                                                    style="background-color: #2563eb; color: white; padding: 4px 10px; border-radius: 4px; font-size: 10px; font-weight: 900; border: none; cursor: pointer; text-transform: uppercase;">Guardar</button>
                                                                <button wire:click="cancelarPerfil"
                                                                    style="background-color: #94a3b8; color: white; padding: 4px 10px; border-radius: 4px; font-size: 10px; font-weight: 900; border: none; cursor: pointer; text-transform: uppercase;">X</button>
                                                            </div>
                                                        </div>
                                                    @else
                                                        {{-- MODO VISTA --}}
                                                        <div class="flex items-center gap-2 flex-grow overflow-hidden">
                                                            <span
                                                                class="font-bold {{ str_contains($colorClase['text'], 'white') ? 'text-slate-700' : 'text-slate-600' }} truncate">
                                                                {{ $jugador->nombre }}
                                                            </span>

                                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                                @if ($jugador->elo)
                                                                    <span
                                                                        class="text-[10px] font-bold bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded border border-blue-200">
                                                                        {{ $jugador->elo }}
                                                                    </span>
                                                                @endif

                                                                @if ($jugador->edad || $jugador->genero)
                                                                    <span
                                                                        class="text-[9px] text-slate-400 font-black uppercase">
                                                                        ({{ $jugador->edad ?? '?' }}a,
                                                                        {{ $jugador->genero == 'Masculino' ? 'M' : ($jugador->genero == 'Femenino' ? 'F' : 'O') }})
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="flex gap-1 ml-auto">
                                                            <button wire:click="editarPerfil({{ $jugador->id }})"
                                                                style="background-color: rgba(71, 85, 105, 0.1); color: #475569; padding: 6px; border-radius: 6px; border: none; cursor: pointer; display: flex; align-items: center;"
                                                                title="Datos del Jugador (Elo/Edad)">
                                                                <svg style="width: 16px; height: 16px;" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2.5"
                                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                                </svg>
                                                            </button>

                                                            <button
                                                                wire:click="editarJugador({{ $jugador->id }}, '{{ $jugador->nombre }}')"
                                                                style="background-color: rgba(37, 99, 235, 0.1); color: #2563eb; padding: 6px; border-radius: 6px; border: none; cursor: pointer; display: flex; align-items: center;"
                                                                title="Editar Nombre">
                                                                <svg style="width: 16px; height: 16px;" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2.5"
                                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    @endif
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>


                                </div>

                                <div
                                    class="px-6 py-2 bg-slate-50 border-t border-slate-100 flex justify-center italic">
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Rook
                                        Systems</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>




            {{-- SECCIÓN EMPAREJAMIENTOS --}}
            <section class="mt-12">
                {{-- Título con acento dorado --}}
                <div class="flex items-center gap-4 mb-10">
                    <div class="h-10 w-3 bg-[#c5a059] rounded-full shadow-[0_0_15px_rgba(197,160,89,0.4)]"></div>
                    <div>
                        <h2 class="text-3xl font-black text-slate-800 uppercase tracking-tight">Administrar Rondas del
                            Torneo</h2>
                        <p class="text-sm text-slate-400 font-medium italic">Asigna los puntajes de cada ronda y de
                            cada
                            partida</p>
                    </div>
                </div>

                {{-- Contenedor de Rondas --}}
                <div class="relative p-1 bg-slate-100 rounded-3xl border border-slate-200">
                    {{-- Fondo decorativo de tablero muy sutil --}}
                    <div class="absolute inset-0 opacity-[0.03] rounded-3xl"
                        style="background-image: conic-gradient(#000 0.25turn, #fff 0.25turn 0.5turn, #000 0.5turn 0.75turn, #fff 0.75turn); background-size: 40px 40px;">
                    </div>

                    <div class="relative z-10 flex flex-wrap justify-center gap-6 p-10">
                        @foreach ($torneo->rondas->sortBy('numero') as $ronda)
                            <button wire:click="verRonda({{ $ronda->id }}, {{ $ronda->numero }})"
                                class="group relative flex flex-col items-center justify-center w-32 h-32 bg-white rounded-2xl shadow-sm border-b-4 border-slate-300 transition-all duration-300 hover:-translate-y-2 hover:border-[#c5a059] hover:shadow-xl active:scale-95 overflow-hidden">
                                {{-- Indicador de número superior --}}
                                <span
                                    class="absolute top-2 right-3 text-[10px] font-black text-slate-300 group-hover:text-[#c5a059]/30 transition-colors">
                                    #0{{ $ronda->numero }}
                                </span>

                                {{-- Icono visual (Peón o Reloj) --}}
                                <div class="mb-1 text-2xl group-hover:scale-125 transition-transform duration-300">
                                    <span class="text-slate-400 group-hover:text-[#c5a059]">⏲</span>
                                </div>

                                {{-- Texto principal --}}
                                <span
                                    class="text-xs uppercase font-bold text-slate-400 group-hover:text-slate-500 tracking-tighter">Fase
                                    de Grupo</span>
                                <span class="text-xl font-black text-slate-700 group-hover:text-slate-900">
                                    Ronda {{ $ronda->numero }}
                                </span>

                                {{-- Efecto de brillo inferior --}}
                                <div
                                    class="absolute bottom-0 left-0 w-0 h-1 bg-[#c5a059] group-hover:w-full transition-all duration-500">
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </section>

    </main>

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
                            $equipo1 =
                                optional(optional($partidas->first())->jugadorBlancas)->equipo->nombre ?? 'Equipo A';
                            $equipo2 =
                                optional(optional($partidas->first())->jugadorNegras)->equipo->nombre ?? 'Equipo B';
                        @endphp

                        <div class="mb-10 last:mb-0">
                            <div class="flex items-center justify-between mb-4 bg-slate-900 p-3 rounded-lg shadow-md">
                                <span
                                    class="text-white font-bold px-3 py-1 bg-[#c5a059] rounded text-sm uppercase">Estación
                                    {{ $estacion }}</span>
                                <span class="text-[#c5a059] font-bold md:text-lg italic">{{ $equipo1 }} <span
                                        class="text-white mx-2">VS</span> {{ $equipo2 }}</span>
                            </div>

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
                                                <span class="text-[11px] text-[#c5a059] font-semibold">
                                                    {{ $emp->jugadorBlancas?->equipo?->nombre }}
                                                </span>

                                            </div>
                                        </div>

                                        {{-- VS --}}
                                        <div class="md:col-span-1 flex flex-col items-center justify-center gap-2">

                                            {{-- Mesa --}}
                                            <span
                                                class="bg-slate-200 text-slate-500 text-xs font-black px-2 py-1 rounded-full uppercase">
                                                Mesa {{ $emp->mesa }}
                                            </span>

                                            {{-- SELECT RESULTADO --}}
                                            <select
                                                wire:change="guardarResultado({{ $emp->id }}, $event.target.value)"
                                                class="border border-slate-300 rounded px-2 py-1 text-xs font-bold bg-white focus:ring-2 focus:ring-[#c5a059]">
                                                <option value="">-</option>
                                                <option value="1-0"
                                                    {{ $emp->resultado == '1-0' ? 'selected' : '' }}>1-0</option>
                                                <option value="0-1"
                                                    {{ $emp->resultado == '0-1' ? 'selected' : '' }}>0-1</option>
                                                <option value="1-1"
                                                    {{ $emp->resultado == '1-1' ? 'selected' : '' }}>1-1</option>
                                            </select>

                                        </div>
                                        {{-- NEGRAS --}}
                                        <div class="md:col-span-3 flex items-center justify-end gap-4 text-right">
                                            <div class="flex flex-col">
                                                <span
                                                    class="font-bold text-slate-800">{{ $emp->jugadorNegras?->nombre }}</span>
                                                <span class="text-[10px] uppercase font-bold text-slate-400">Tablero
                                                    {{ $emp->jugadorNegras?->tablero }}</span>
                                                <span class="text-[11px] text-[#c5a059] font-semibold">
                                                    {{ $emp->jugadorNegras?->equipo?->nombre }}
                                                </span>
                                            </div>
                                            <div
                                                class="w-10 h-10 flex-shrink-0 bg-slate-800 border-2 border-slate-900 rounded-full flex items-center justify-center shadow-sm">
                                                <span class="text-xl text-white">♚</span>
                                            </div>
                                        </div>

                                    </div>
                                @endforeach
                            </div>



                            @php
                                $equiposIds = $partidas
                                    ->map(function ($p) {
                                        return [$p->jugadorBlancas->equipo_id, $p->jugadorNegras->equipo_id];
                                    })
                                    ->flatten()
                                    ->unique();

                                $resultados = ResultadoEquipo::where('ronda_id', $partidas->first()->ronda_id)
                                    ->whereIn('equipo_id', $equiposIds)
                                    ->get();
                            @endphp
                            <!-- mostrar resultados por equipos -->

                            {{-- RESULTADOS DEL ENCUENTRO POR EQUIPOS --}}
                            @if ($resultados->count())
                                <div
                                    class="mt-6 relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50 shadow-inner">
                                    {{-- Decoración lateral sutil --}}
                                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-[#c5a059]"></div>

                                    <div class="p-4">
                                        <h4
                                            class="text-[10px] uppercase font-black text-slate-400 tracking-[0.2em] mb-3 flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-[#c5a059] animate-pulse"></span>
                                            Resumen de Puntuación
                                        </h4>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            @foreach ($resultados as $res)
                                                <div
                                                    class="flex items-center justify-between p-3 bg-white rounded-lg border border-slate-100 shadow-sm group hover:border-[#c5a059]/30 transition-colors">
                                                    <div class="flex flex-col">
                                                        <span
                                                            class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">Equipo</span>
                                                        <span
                                                            class="font-black text-slate-700 leading-none group-hover:text-[#c5a059] transition-colors">
                                                            {{ $res->equipo->nombre }}
                                                        </span>
                                                    </div>

                                                    <div class="flex gap-3">
                                                        {{-- Puntos Individuales --}}
                                                        <div
                                                            class="flex flex-col items-end border-r border-slate-100 pr-3">
                                                            <span
                                                                class="text-[9px] text-slate-400 font-bold uppercase">Indiv.</span>
                                                            <span
                                                                class="text-sm font-bold text-slate-600">{{ $res->puntos_individuales }}
                                                                <span class="text-[10px]">pts</span></span>
                                                        </div>

                                                        {{-- Puntos Globales (El "Marcador") --}}
                                                        <div class="flex flex-col items-end">
                                                            <span
                                                                class="text-[9px] text-[#c5a059] font-black uppercase">Global</span>
                                                            <span
                                                                class="text-lg font-black text-slate-900 leading-none">
                                                                {{ $res->puntos_globales }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- FOOTER --}}
    <footer class="relative mt-20 bg-slate-900 pt-16 pb-10 overflow-hidden">
        {{-- Decoración: Borde superior dorado brillante --}}
        <div
            class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-transparent via-[#c5a059] to-transparent opacity-70">
        </div>

        {{-- Decoración: Fondo de tablero sutil (Solo se nota en pantallas grandes) --}}
        <div class="absolute inset-0 opacity-5 pointer-events-none"
            style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 30px 30px;"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4">
            <div class="flex flex-col items-center">

                {{-- Logotipo/Nombre en el Footer --}}
                <div class="mb-6 flex flex-col items-center md:flex-row gap-4">
                    {{-- Contenedor del Icono: Ahora cuadrado con bordes suavizados para simular una torre --}}
                    <div
                        class="h-10 w-10 bg-[#c5a059] rounded-lg flex items-center justify-center shadow-[0_0_20px_rgba(197,160,89,0.4)] transform rotate-3 hover:rotate-0 transition-transform duration-300">
                        {{-- Icono de Torre (Rook) --}}
                        <span class="text-white text-2xl font-serif">♜</span>
                    </div>

                    {{-- Texto de Marca --}}
                    <div class="flex flex-col leading-none">
                        <span class="text-2xl font-black text-white uppercase tracking-tighter">
                            ROOK <span class="text-[#c5a059]">SYSTEMS</span>
                        </span>
                        <span class="text-[9px] text-slate-500 font-bold tracking-[0.3em] uppercase mt-1">
                            Engineering Strategy
                        </span>
                    </div>
                </div>

                {{-- Frase Motivacional o Info Extra --}}
                <p class="text-slate-500 text-sm max-w-md text-center mb-8 italic leading-relaxed">
                    "La mente es el tablero donde se ganan las batallas antes de mover la primera pieza."
                </p>

                {{-- Línea Divisoria Estilizada --}}
                <div class="flex items-center gap-4 mb-8 w-full max-w-xs">
                    <div class="h-[1px] flex-1 bg-gradient-to-r from-transparent to-slate-700"></div>
                    <div class="w-2 h-2 rotate-45 border border-[#c5a059]"></div>
                    <div class="h-[1px] flex-1 bg-gradient-to-l from-transparent to-slate-700"></div>
                </div>

                {{-- Copyright y Créditos --}}
                <div
                    class="flex flex-col md:flex-row items-center gap-4 text-[10px] font-bold tracking-[0.2em] uppercase">
                    <span class="text-[#c5a059]">© 2026 RookSystems</span>
                    <span class="hidden md:block text-slate-700">|</span>
                    <span class="text-slate-400">Desarrollado por Axel Javier Alvarez</span>
                </div>

                {{-- Badge de Calidad (Opcional) --}}
                <div class="mt-6">
                    <span class="px-3 py-1 border border-slate-800 rounded-full text-[9px] text-slate-600 uppercase">
                        Estrategia · Disciplina · Honor
                    </span>
                </div>
            </div>
        </div>
    </footer>

</div>

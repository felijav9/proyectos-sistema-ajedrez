<?php

use Livewire\Component;
use App\Models\Torneo;
use App\Models\Emparejamiento;
use App\Models\Equipo;

new class extends Component {

    public $torneo;
    public $equipos;
    public $emparejamientos = [];
    public $rondaSeleccionada = null;
    public $openModal = false;

    public function mount()
    {
        $this->torneo = Torneo::with('rondas')->find(1);

        // cargar equipos con jugadores
        $this->equipos = Equipo::with('jugadores')->get();
    }

    public function verRonda($rondaId, $numero)
    {
        $this->rondaSeleccionada = $numero;

        $this->emparejamientos = Emparejamiento::with([
            'jugadorBlancas.equipo',
            'jugadorNegras.equipo'
        ])
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

};
?>

<div class="min-h-screen bg-slate-50 font-sans text-slate-900 pb-12">

    {{-- HEADER / TITULO --}}
    <header class="bg-[#c5a059] py-10 mb-10 shadow-lg relative overflow-hidden">
        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#000 1px, transparent 1px); background-size: 20px 20px;"></div>

        <div class="max-w-7xl mx-auto px-4 relative z-10">
            <h1 class="text-4xl md:text-5xl font-black text-white text-center uppercase tracking-tighter drop-shadow-md">
                {{ $torneo->nombre }}
            </h1>
            <div class="w-24 h-1 bg-white mx-auto mt-4 rounded-full"></div>
        </div>
    </header>





    <main class="max-w-7xl mx-auto px-4">

        {{-- ========================================== --}}
{{-- SECCIÓN 1: CENTRO DE INFORMACIÓN (TABS)    --}}
{{-- ========================================== --}}
<section class="mb-12" x-data="{ tab: 'reglas' }">
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
        {{-- Navegación de Pestañas --}}
        <div class="flex border-b border-slate-100 bg-slate-50">
            <button @click="tab = 'reglas'"
                :class="tab === 'reglas' ? 'bg-white text-[#c5a059] border-t-4 border-t-[#c5a059]' : 'text-slate-400 hover:text-slate-600'"
                class="flex-1 py-5 px-6 font-bold uppercase text-[10px] tracking-[0.2em] transition-all outline-none flex items-center justify-center gap-2">
                <span>📜</span> REGLAMENTO ESTRATÉGICO
            </button>
            <button @click="tab = 'puntos'"
                :class="tab === 'puntos' ? 'bg-white text-[#c5a059] border-t-4 border-t-[#c5a059]' : 'text-slate-400 hover:text-slate-600'"
                class="flex-1 py-5 px-6 font-bold uppercase text-[10px] tracking-[0.2em] transition-all outline-none flex items-center justify-center gap-2">
                <span>📈</span> CRITERIOS DE PUNTUACIÓN
            </button>
        </div>

        {{-- Contenido Pestañas --}}
        <div class="p-8 md:p-10">
            {{-- REGLAS DE COMPETICIÓN --}}
            <div x-show="tab === 'reglas'" x-transition.opacity>
                <div class="flex items-center gap-3 mb-6">
                    <h3 class="text-2xl font-black text-slate-800 uppercase italic tracking-tight">Manual de Juego</h3>
                    <div class="flex-1 h-px bg-slate-100"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-slate-600">
                    {{-- Formato --}}
                    <div class="flex items-start gap-4 p-5 bg-slate-50 rounded-2xl border border-slate-100 group hover:border-[#c5a059]/30 transition-colors">
                        <div class="bg-[#c5a059] text-white w-8 h-8 rounded-lg flex items-center justify-center font-black flex-shrink-0 shadow-md">01</div>
                        <div>
                            <p class="font-black text-slate-800 uppercase text-[11px] mb-1 tracking-widest">Formato de Encuentro</p>
                            <p>Cada equipo compite en <strong>10 rondas</strong>. Los duelos se dividen en tableros específicos (A vs A, A vs B, C vs D), asegurando que cada miembro juegue 10 partidas individuales.</p>
                        </div>
                    </div>

                    {{-- Desempate --}}
                    <div class="flex items-start gap-4 p-5 bg-slate-50 rounded-2xl border border-slate-100 group hover:border-[#c5a059]/30 transition-colors">
                        <div class="bg-slate-900 text-white w-8 h-8 rounded-lg flex items-center justify-center font-black flex-shrink-0 shadow-md">02</div>
                        <div>
                            <p class="font-black text-[#c5a059] uppercase text-[11px] mb-1 tracking-widest">Sistema de Desempate</p>
                            <p>Si hay empate en <strong>Puntos Globales</strong> al final del torneo, el ganador se decidirá por los <strong>Puntos de Mesa</strong> acumulados (puntos de todas las partidas individuales).</p>
                        </div>
                    </div>

                    {{-- Equipamiento --}}
                    <div class="flex items-start gap-4 p-5 bg-slate-50 rounded-2xl border border-slate-100 group hover:border-[#c5a059]/30 transition-colors">
                        <div class="bg-slate-900 text-white w-8 h-8 rounded-lg flex items-center justify-center font-black flex-shrink-0 shadow-md">03</div>
                        <div>
                            <p class="font-black text-[#c5a059] uppercase text-[11px] mb-1 tracking-widest">Ritmo y Reloj</p>
                            <p>Es obligatorio el uso de <strong>Chessclock</strong>. El ritmo es <strong>Blitz (5+0)</strong>. No se permite ayuda externa (Fair Play estricto).</p>
                        </div>
                    </div>

                    {{-- Pieza Tocada --}}
                    <div class="flex items-start gap-4 p-5 bg-slate-50 rounded-2xl border border-slate-100 group hover:border-[#c5a059]/30 transition-colors">
                        <div class="bg-[#c5a059] text-white w-8 h-8 rounded-lg flex items-center justify-center font-black flex-shrink-0 shadow-md">04</div>
                        <div>
                            <p class="font-black text-slate-800 uppercase text-[11px] mb-1 tracking-widest">Leyes de la FIDE</p>
                            <p>Se aplica la regla de <strong>Pieza Tocada, Pieza Movida</strong>. Una vez completado el movimiento legal, no hay marcha atrás.</p>
                        </div>
                    </div>
                </div>


               {{-- ACA PEEGAR EL CODIGO --}}
  <div class="border-t border-slate-100 pt-10">
    <h4 class="text-center text-xl font-black text-slate-800 uppercase tracking-tighter mb-8 italic">
        🏆 Reconocimiento al Esfuerzo Colectivo
    </h4>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        {{-- ORO --}}
        <div class="bg-gradient-to-b from-yellow-50 to-white p-6 rounded-3xl border border-yellow-200 text-center shadow-sm hover:shadow-md transition-shadow">
            <div class="flex justify-center -space-x-3 mb-4">
                @for ($i = 0; $i < 4; $i++)
                    <span class="text-4xl drop-shadow-md animate-bounce" style="animation-delay: {{ $i * 0.1 }}s">🥇</span>
                @endfor
            </div>
            <p class="font-black text-yellow-700 uppercase text-xs tracking-widest">1er Puesto</p>
            <p class="text-[10px] text-yellow-600/70 font-bold mt-1">4 Medallas de Oro<br>(Una para cada integrante)</p>
        </div>

        {{-- PLATA --}}
        <div class="bg-gradient-to-b from-slate-50 to-white p-6 rounded-3xl border border-slate-200 text-center shadow-sm hover:shadow-md transition-shadow">
            <div class="flex justify-center -space-x-3 mb-4">
                @for ($i = 0; $i < 4; $i++)
                    {{-- Usamos un delay un poco diferente para que no coincida exactamente con el oro --}}
                    <span class="text-4xl drop-shadow-md animate-bounce" style="animation-delay: {{ ($i * 0.1) + 0.5 }}s">🥈</span>
                @endfor
            </div>
            <p class="font-black text-slate-700 uppercase text-xs tracking-widest">2do Puesto</p>
            <p class="text-[10px] text-slate-500/70 font-bold mt-1">4 Medallas de Plata<br>(Una para cada integrante)</p>
        </div>

        {{-- BRONCE --}}
        <div class="bg-gradient-to-b from-orange-50 to-white p-6 rounded-3xl border border-orange-200 text-center shadow-sm hover:shadow-md transition-shadow">
            <div class="flex justify-center -space-x-3 mb-4">
                @for ($i = 0; $i < 4; $i++)
                    <span class="text-4xl drop-shadow-md animate-bounce" style="animation-delay: {{ ($i * 0.1) + 1 }}s">🥉</span>
                @endfor
            </div>
            <p class="font-black text-orange-800 uppercase text-xs tracking-widest">3er Puesto</p>
            <p class="text-[10px] text-orange-700/70 font-bold mt-1">4 Medallas de Bronce<br>(Una para cada integrante)</p>
        </div>
    </div>
</div>


            </div>

            {{-- SISTEMA DE PUNTUACIÓN --}}
            <div x-show="tab === 'puntos'" x-transition.opacity>
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                    <div>
                        <h3 class="text-2xl font-black text-slate-800 uppercase italic tracking-tight italic">Puntos Globales (Por Equipo)</h3>
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">Estos puntos definen la tabla general</p>
                    </div>
                    <div class="px-4 py-2 bg-[#c5a059]/10 rounded-full border border-[#c5a059]/20">
                        <span class="text-[10px] font-black text-[#c5a059] uppercase tracking-tighter">Criterio: Resultado por Ronda</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- Ganado --}}
                    <div class="relative group bg-slate-900 p-8 rounded-3xl text-center shadow-2xl transition-all hover:-translate-y-2">
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-[#c5a059] text-white text-[9px] font-black px-4 py-1 rounded-full uppercase tracking-widest">Ganador</div>
                        <span class="block text-5xl font-black text-[#c5a059] mb-2">2</span>
                        <span class="text-[10px] uppercase tracking-[0.2em] font-bold text-white opacity-60">Puntos Globales</span>
                        <p class="mt-4 text-xs text-slate-400 font-medium leading-tight">Si el equipo suma más victorias individuales en la ronda.</p>
                    </div>

                    {{-- Empate --}}
                    <div class="relative group bg-white border-2 border-slate-100 p-8 rounded-3xl text-center transition-all hover:-translate-y-2">
                        <span class="block text-5xl font-black text-slate-800 mb-2">1</span>
                        <span class="text-[10px] uppercase tracking-[0.2em] font-bold text-slate-400">Punto Global</span>
                        <p class="mt-4 text-xs text-slate-500 font-medium leading-tight">Si ambos equipos terminan con la misma cantidad de victorias.</p>
                    </div>

                    {{-- Perdido --}}
                    <div class="relative group bg-white border-2 border-slate-100 p-8 rounded-3xl text-center transition-all hover:-translate-y-2 opacity-60">
                        <span class="block text-5xl font-black text-slate-300 mb-2">0</span>
                        <span class="text-[10px] uppercase tracking-[0.2em] font-bold text-slate-400">Puntos Globales</span>
                        <p class="mt-4 text-xs text-slate-500 font-medium leading-tight">Si el equipo tiene menos victorias que el rival en la ronda.</p>
                    </div>
                </div>

                {{-- Nota Aclaratoria --}}
                <div class="mt-10 p-4 bg-slate-50 rounded-2xl border-l-4 border-slate-300 flex items-center justify-between">
                    <p class="text-[11px] text-slate-500 font-bold uppercase tracking-wide">
                        <span class="text-slate-800">Nota:</span> Los puntos de las partidas individuales se guardan para el desempate final.
                    </p>
                    <span class="text-xl">🏆</span>
                </div>

                {{-- ACA PEGO CODIGO --}}
               <div class="mt-10 space-y-8">
    {{-- Título de la subsección --}}
    <div class="flex items-center gap-3">
        <h4 class="text-sm font-black text-slate-400 uppercase tracking-[0.2em]">Ejemplos Prácticos</h4>
        <div class="flex-1 h-px bg-slate-100"></div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        {{-- ESCENARIO 1: VICTORIA --}}
        <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm">
            <div class="bg-slate-900 p-4 text-center">
                <p class="text-[10px] font-black text-[#c5a059] uppercase tracking-widest">Escenario de Victoria</p>
            </div>
            <div class="p-6 space-y-3">
                <div class="flex justify-between text-[10px] font-black text-slate-400 uppercase px-2">
                    <span>Duelo por Mesa</span>
                    <span>Resultado</span>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between items-center p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="font-bold text-slate-700">Mesa A <span class="text-[9px] text-slate-400 mx-2">VS</span> Mesa A</span>
                        <span class="font-black text-green-600">Gana Equipo X (+1)</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="font-bold text-slate-700">Mesa B <span class="text-[9px] text-slate-400 mx-2">VS</span> Mesa B</span>
                        <span class="font-black text-blue-600">Gana Equipo Y (+1)</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="font-bold text-slate-700">Mesa C <span class="text-[9px] text-slate-400 mx-2">VS</span> Mesa C</span>
                        <span class="font-black text-blue-600">Gana Equipo Y (+1)</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="font-bold text-slate-700">Mesa D <span class="text-[9px] text-slate-400 mx-2">VS</span> Mesa D</span>
                        <span class="font-black text-slate-500 italic text-[11px]">Tablas (0.5 cada uno)</span>
                    </div>
                </div>
                <div class="pt-4 border-t border-dashed border-slate-200 flex justify-between items-end">
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase">Total Puntos Mesa</p>
                        <p class="font-bold text-slate-600">X: 1.5 | <span class="text-blue-600">Y: 2.5</span></p>
                    </div>
                    <div class="text-right">
                        <p class="text-[9px] font-black text-[#c5a059] uppercase italic">Resultado Final</p>
                        <p class="font-black text-slate-900 text-lg">Equipo Y suma <span class="text-[#c5a059]">2 pts</span> Globales</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ESCENARIO 2: EMPATE --}}
        <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm">
            <div class="bg-slate-100 p-4 text-center border-b border-slate-200">
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Escenario de Empate</p>
            </div>
            <div class="p-6 space-y-3">
                <div class="flex justify-between text-[10px] font-black text-slate-400 uppercase px-2">
                    <span>Duelo por Mesa</span>
                    <span>Resultado</span>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between items-center p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="font-bold text-slate-700">Mesa A <span class="text-[9px] text-slate-400 mx-2">VS</span> Mesa A</span>
                        <span class="font-black text-green-600">Gana Equipo X (+1)</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="font-bold text-slate-700">Mesa B <span class="text-[9px] text-slate-400 mx-2">VS</span> Mesa B</span>
                        <span class="font-black text-blue-600">Gana Equipo Y (+1)</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="font-bold text-slate-700">Mesa C <span class="text-[9px] text-slate-400 mx-2">VS</span> Mesa C</span>
                        <span class="font-black text-slate-500 italic text-[11px]">Tablas (0.5 cada uno)</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="font-bold text-slate-700">Mesa D <span class="text-[9px] text-slate-400 mx-2">VS</span> Mesa D</span>
                        <span class="font-black text-slate-500 italic text-[11px]">Tablas (0.5 cada uno)</span>
                    </div>
                </div>
                <div class="pt-4 border-t border-dashed border-slate-200 flex justify-between items-end">
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase">Total Puntos Mesa</p>
                        <p class="font-bold text-slate-600">X: 2.0 | Y: 2.0</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[9px] font-black text-slate-400 uppercase italic">Resultado Final</p>
                        <p class="font-black text-slate-900 text-lg">Ambos suman <span class="text-slate-500">1 pt</span> Global</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recordatorio final --}}
    <div class="p-4 bg-blue-50 rounded-2xl border border-blue-100 flex items-center gap-3">
        <span class="text-xl">💡</span>
        <p class="text-xs text-blue-700 font-medium">
            <strong>Recuerda:</strong> Los Puntos de Mesa (X: 1.5, Y: 2.5, etc.) son los que te salvarán en caso de empate en la tabla general al final del torneo. ¡Cada media partida cuenta!
        </p>
    </div>
</div>





            </div>



        </div>
    </div>
</section>


        <section class="mb-16" x-data="{ open: false }">
            <div @click="open = !open"
                class="flex items-center justify-between cursor-pointer group bg-slate-900 p-6 rounded-2xl shadow-xl transition-all mb-8 border-l-8 border-[#c5a059]">
                <div class="flex items-center gap-4">
                    <span class="text-2xl text-[#c5a059] animate-bounce-slow">♜</span>
                    <h2 class="text-2xl font-bold text-white tracking-tight uppercase">Equipos Participantes</h2>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-[10px] font-black text-[#c5a059] uppercase tracking-widest" x-text="open ? 'Colapsar' : 'Desplegar Lista'"></span>
                    <div class="bg-slate-800 p-1 rounded-full text-white transition-transform duration-300" :class="open ? 'rotate-180' : ''">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>
            </div>

            <div x-show="open" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 -translate-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        @foreach($equipos->sortBy('nombre') as $equipo)
                        @php
                            $nombreLower = strtolower($equipo->nombre);
                            $colorClase = match(true) {
                                str_contains($nombreLower, 'campeones') => ['border' => 'border-red-500', 'text' => 'text-red-600'],
                                str_contains($nombreLower, 'bloops') => ['border' => 'border-white', 'text' => 'text-white'],
                                str_contains($nombreLower, 'apertura maestra') => ['border' => 'border-gray-400', 'text' => 'text-gray-400'],
                                str_contains($nombreLower, 'gambitos') && !str_contains($nombreLower, 'dama') => ['border' => 'border-green-500', 'text' => 'text-green-600'],
                                str_contains($nombreLower, 'gambito de dama') => ['border' => 'border-blue-500', 'text' => 'text-blue-600'],
                                str_contains($nombreLower, 'changos') => ['border' => 'border-pink-400', 'text' => 'text-pink-500'],
                                default => ['border' => 'border-[#c5a059]', 'text' => 'text-[#c5a059]'],
                            };
                        @endphp

                        <div class="group relative bg-white border-t-4 {{ $colorClase['border'] }} rounded-xl shadow-md hover:shadow-2xl transition-all duration-300 overflow-hidden">
                            <div class="bg-slate-900 p-5 text-center relative z-10 border-b border-slate-800">
                                <h3 class="font-black text-xl {{ $colorClase['text'] }} uppercase tracking-widest group-hover:scale-105 transition-transform">
                                    {{ $equipo->nombre }}
                                </h3>
                            </div>
                            <div class="p-6">
                                <ul class="space-y-3">
                                    @foreach($equipo->jugadores->sortBy('tablero') as $jugador)
                                        <li class="flex items-center justify-between border-b border-slate-50 pb-2 last:border-0 group/item">
                                            <div class="flex items-center gap-3">
                                                <span class="{{ $colorClase['text'] == 'text-white' ? 'text-slate-400' : $colorClase['text'] }} text-lg">♟</span>
                                                <span class="ml-1 text-[9px] font-black bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded">{{ $jugador->tablero }}</span>
                                                <span class="font-bold {{ $colorClase['text'] == 'text-white' ? 'text-slate-700' : 'text-slate-600' }}">{{ $jugador->nombre }}</span>
                                                @if($jugador->tablero == 'A')
                                                    <span class="text-[8px] font-black bg-[#c5a059] text-white px-2 py-0.5 rounded-full uppercase tracking-tighter animate-pulse">Capitán</span>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="px-6 py-2 bg-slate-50 border-t border-slate-100 flex justify-center italic">
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Rook Systems</span>
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
            <h2 class="text-3xl font-black text-slate-800 uppercase tracking-tight">Rondas del Torneo</h2>
            <p class="text-sm text-slate-400 font-medium italic">Selecciona una ronda para ver los duelos</p>
        </div>
    </div>

    {{-- Contenedor de Rondas --}}
    <div class="relative p-1 bg-slate-100 rounded-3xl border border-slate-200">
        {{-- Fondo decorativo de tablero muy sutil --}}
        <div class="absolute inset-0 opacity-[0.03] rounded-3xl" style="background-image: conic-gradient(#000 0.25turn, #fff 0.25turn 0.5turn, #000 0.5turn 0.75turn, #fff 0.75turn); background-size: 40px 40px;"></div>

        <div class="relative z-10 flex flex-wrap justify-center gap-6 p-10">
            @foreach($torneo->rondas->sortBy('numero') as $ronda)
                <button
                    wire:click="verRonda({{ $ronda->id }}, {{ $ronda->numero }})"
                    class="group relative flex flex-col items-center justify-center w-32 h-32 bg-white rounded-2xl shadow-sm border-b-4 border-slate-300 transition-all duration-300 hover:-translate-y-2 hover:border-[#c5a059] hover:shadow-xl active:scale-95 overflow-hidden"
                >
                    {{-- Indicador de número superior --}}
                    <span class="absolute top-2 right-3 text-[10px] font-black text-slate-300 group-hover:text-[#c5a059]/30 transition-colors">
                        #0{{ $ronda->numero }}
                    </span>

                    {{-- Icono visual (Peón o Reloj) --}}
                    <div class="mb-1 text-2xl group-hover:scale-125 transition-transform duration-300">
                        <span class="text-slate-400 group-hover:text-[#c5a059]">⏲</span>
                    </div>

                    {{-- Texto principal --}}
                    <span class="text-xs uppercase font-bold text-slate-400 group-hover:text-slate-500 tracking-tighter">Fase de Grupo</span>
                    <span class="text-xl font-black text-slate-700 group-hover:text-slate-900">
                        Ronda {{ $ronda->numero }}
                    </span>

                    {{-- Efecto de brillo inferior --}}
                    <div class="absolute bottom-0 left-0 w-0 h-1 bg-[#c5a059] group-hover:w-full transition-all duration-500"></div>
                </button>
            @endforeach
        </div>
    </div>
</section>

    </main>

    {{-- MODAL ESTILO PREMIUM --}}
    @if($openModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        {{-- Backdrop con blur --}}
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="cerrarModal"></div>

        <div class="relative bg-white w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col border-t-8 border-[#c5a059]">

            {{-- Modal Header --}}
            <div class="p-6 border-b flex justify-between items-center bg-slate-50">
                <div>
                    <h2 class="text-2xl font-black text-slate-800 uppercase italic">Enfrentamientos</h2>
                    <p class="text-[#c5a059] font-bold">Ronda {{ $rondaSeleccionada }}</p>
                </div>
                <button wire:click="cerrarModal" class="p-2 hover:bg-red-50 text-slate-400 hover:text-red-500 rounded-full transition-colors text-2xl">
                    ✕
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 overflow-y-auto bg-white">
                @foreach($emparejamientos->groupBy('estacion') as $estacion => $partidas)
                @php
                    $equipo1 = optional(optional($partidas->first())->jugadorBlancas)->equipo->nombre ?? 'Equipo A';
                    $equipo2 = optional(optional($partidas->first())->jugadorNegras)->equipo->nombre ?? 'Equipo B';
                @endphp

                <div class="mb-10 last:mb-0">
                    <div class="flex items-center justify-between mb-4 bg-slate-900 p-3 rounded-lg shadow-md">
                        <span class="text-white font-bold px-3 py-1 bg-[#c5a059] rounded text-sm uppercase">Estación {{ $estacion }}</span>
                        <span class="text-[#c5a059] font-bold md:text-lg italic">{{ $equipo1 }} <span class="text-white mx-2">VS</span> {{ $equipo2 }}</span>
                    </div>

                    <div class="grid grid-cols-1 gap-3">
                        @foreach($partidas as $emp)
                        <div class="grid grid-cols-1 md:grid-cols-7 items-center bg-slate-50 border border-slate-200 rounded-xl p-4 hover:bg-[#c5a059]/5 transition-colors group">

                            {{-- BLANCAS --}}
                            <div class="md:col-span-3 flex items-center gap-4">
                                <div class="w-10 h-10 flex-shrink-0 bg-white border-2 border-slate-300 rounded-full flex items-center justify-center shadow-sm">
                                    <span class="text-xl">♔</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-800">{{ $emp->jugadorBlancas?->nombre }}</span>
                                    <span class="text-[10px] uppercase font-bold text-slate-400">Tablero {{ $emp->jugadorBlancas?->tablero }}</span>
                                    <span class="text-[11px] text-[#c5a059] font-semibold">
                                                {{ $emp->jugadorBlancas?->equipo?->nombre }}
                                            </span>

                                </div>
                            </div>

                            {{-- VS --}}
                            <div class="md:col-span-1 flex justify-center py-2 md:py-0">
                                <span class="bg-slate-200 text-slate-500 text-xs font-black px-2 py-1 rounded-full group-hover:bg-[#c5a059] group-hover:text-white transition-colors uppercase">Mesa {{ $emp->mesa }}</span>
                            </div>

                            {{-- NEGRAS --}}
                            <div class="md:col-span-3 flex items-center justify-end gap-4 text-right">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-800">{{ $emp->jugadorNegras?->nombre }}</span>
                                    <span class="text-[10px] uppercase font-bold text-slate-400">Tablero {{ $emp->jugadorNegras?->tablero }}</span>
                                    <span class="text-[11px] text-[#c5a059] font-semibold">
        {{ $emp->jugadorNegras?->equipo?->nombre }}
    </span>
                                </div>
                                <div class="w-10 h-10 flex-shrink-0 bg-slate-800 border-2 border-slate-900 rounded-full flex items-center justify-center shadow-sm">
                                    <span class="text-xl text-white">♚</span>
                                </div>
                            </div>

                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- FOOTER --}}
    <footer class="relative mt-20 bg-slate-900 pt-16 pb-10 overflow-hidden">
    {{-- Decoración: Borde superior dorado brillante --}}
    <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-transparent via-[#c5a059] to-transparent opacity-70"></div>

    {{-- Decoración: Fondo de tablero sutil (Solo se nota en pantallas grandes) --}}
    <div class="absolute inset-0 opacity-5 pointer-events-none" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 30px 30px;"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-4">
        <div class="flex flex-col items-center">

            {{-- Logotipo/Nombre en el Footer --}}
            <div class="mb-6 flex flex-col items-center md:flex-row gap-4">
    {{-- Contenedor del Icono: Ahora cuadrado con bordes suavizados para simular una torre --}}
    <div class="h-10 w-10 bg-[#c5a059] rounded-lg flex items-center justify-center shadow-[0_0_20px_rgba(197,160,89,0.4)] transform rotate-3 hover:rotate-0 transition-transform duration-300">
        {{-- Icono de Torre (Rook) --}}
        <span class="text-white text-2xl font-serif">♜</span>
    </div>

            {{-- Texto de Marca --}}
            <div class="flex flex-col leading-none">
                <span class="text-2xl font-black text-white uppercase tracking-tighter">
                    ROOK <span class="text-[#c5a059]">SYSTEMS</span>
                </span>
                <span class="text-[9px] text-slate-500 font-bold tracking-[0.3em] uppercase mt-1">
                    Innovation Tech
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
            <div class="flex flex-col md:flex-row items-center gap-4 text-[10px] font-bold tracking-[0.2em] uppercase">
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

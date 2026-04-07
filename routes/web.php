<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\TorneoEmparejamientos;
use Livewire\Volt\Volt;



Route::get('/', function () {
    return view('welcome');
});

Volt::route('/torneo-equipos-marzoddddd', 'torneo-emparejamientos')->name('torneo.index');

 Volt::route('/registrar-resultados', 'registrar-resultados')->name('registrar.resultados');


 Volt::route('/live-results-marzo2026ddddd', 'live-results-marzo2026')->name('live.results');


Route::get('/torneo', TorneoEmparejamientos::class)->name('torneo.index');

<?php

use Illuminate\Support\Facades\Route;
// use App\Livewire\TorneoEmparejamientos;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
});

Volt::route('/torneo-equipos-marzo', 'torneo-emparejamientos')->name('torneo.index');

// Route::get('/torneo', TorneoEmparejamientos::class)->name('torneo.index');
// Route::get('/torneo', TorneoEmparejamientos::class)->name('torneo.index');

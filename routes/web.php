<?php

use Illuminate\Support\Facades\Route;

// Route pour servir le frontend React
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');

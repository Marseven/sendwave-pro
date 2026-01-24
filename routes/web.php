<?php

use Illuminate\Support\Facades\Route;

// Route pour servir le frontend Vue
// Exclut les fichiers statiques (build/, assets, etc.)
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!build|api|storage|favicon).*$');

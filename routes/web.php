<?php

use Illuminate\Support\Facades\Route;

// Route catch-all pour le frontend Vue (SPA)
// Cette route capture toutes les requêtes et renvoie la vue Vue.js
// Les routes API sont définies dans routes/api.php et préfixées par /api
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');

<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/docs', function () {
//     return response()->file(base_path('../assets/docs/index.html'));
// });

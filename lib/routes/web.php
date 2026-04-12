<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // dd(public_path());
    return view('welcome');
});

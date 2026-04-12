<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['api', 'cors', 'json.response'],
    'prefix' => env('API_VERSION') . '/edu'
], function () {

    $featureDirs = File::directories(app_path('Modules/Education'));

    foreach ($featureDirs as $dir) {
        $routeFile = $dir . '/Router/api.php';

        if (File::exists($routeFile)) {
            require $routeFile;
        }
    }
});
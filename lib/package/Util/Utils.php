<?php

/**
 * Created by TeraCore.
 * User: truong.nq
 * Date: 5/8/2020
 * Time: 10:38 PM
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

if (!function_exists('package_path')) {
    function package_path($path = '')
    {
        return '/package/' . $path;
    }
}

if (!function_exists('package_url')) {

    function package_url($url = '')
    {
        return asset('lib/package/' . $url);
    }
}

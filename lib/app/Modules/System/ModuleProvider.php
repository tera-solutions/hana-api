<?php

namespace App\Modules\System;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider as Provider;

class ModuleProvider extends Provider
{
    public function boot()
    {
        $this->activate();
    }

    public function register()
    {
        $this->validation();
    }

    public function activate()
    {
        try {
            $this->loadResource();
        } catch (\Exception $e) {
            return;
        }
    }

    public function deactivate()
    {
        try {
            //
        } catch (\Exception $e) {
            return;
        }
    }

    public function loadResource()
    {
        if (File::exists(__DIR__.'/routes.php')) {
            $this->loadRoutesFrom(__DIR__.'/routes.php');
        }
    }

    public function validation()
    {
        //
    }
}

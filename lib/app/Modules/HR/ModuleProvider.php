<?php

namespace App\Modules\HR;

use Illuminate\Support\ServiceProvider as Provider;
use Illuminate\Support\Facades\File;

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
        if (File::exists(__DIR__ . '/routes.php')) {
            $this->loadRoutesFrom(__DIR__ . '/routes.php');
        }
    }

    public function validation()
    {
        //
    }
}

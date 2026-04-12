<?php

namespace App\Module\Portal;

use Illuminate\Support\ServiceProvider as Provider;
use Illuminate\Support\Facades\File;

/**
 * @property string code
 * @property string role
 */
class ModuleProvider extends Provider
{
    public function __construct($app)
    {
        parent::__construct($app);
    }

    /**
     * Bootstrap services.
     * @return void
     */
    public function boot()
    {
        // install plugin
        //        $this->install();

        // check active plugin
        $this->activate();
    }
    /**
     * Register services.
     * @return void
     */
    public function register()
    {
        // register validation
        $this->validation();
    }

    public function activate()
    {
        try {
            // loading resource
            $this->loadResource();

            // register util
            $this->util();
        } catch (\Exception $e) {
            // notthing
            return;
        }
    }

    public function deactivate()
    {
        try {
            app()->instance(ThemeProvider::class, null);
        } catch (\Exception $e) {
            // notthing
            return;
        }
    }

    public function loadResource()
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/migrations');
        $this->loadFactoriesFrom(__DIR__ . '/Database/factories');
        $this->loadRoutesFrom(__DIR__ . '/Router/api.php');
    }

    public function install()
    {
        $filename = dirname(__FILE__) . '/package.json';
    }

    public function validation()
    {
        //        $this->app->bind('Package\Module\HRM\Entity', function($app)
        //        {
        //            return new CustomerEntity (
        //                $app->make('Package\Module\HRM\Repository'),
        //                new CustomerCreateValidator( $app['validator'] ),
        //                new CustomerUpdateValidator( $app['validator'] )
        //            );
        //        });
    }

    public function util()
    {
        $path_util = __DIR__ . "/Util/Utils.php";

        if (File::exists($path_util)) {
            require_once $path_util;
        }
    }
}

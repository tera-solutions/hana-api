<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \URL::forceScheme(env("HTTP"));

        if (request()->has('lang')) {
            \App::setLocale(request()->get('lang'));
        }

        $this->registerCommands();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->usePublicPath(dirname(base_path()));
    }

    /**
     * Register commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
    }
}

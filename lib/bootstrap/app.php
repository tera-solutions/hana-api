<?php

use App\Http\Middleware\Cors;
use App\Http\Middleware\ForceJsonResponse;
use App\Modules\System\Subscription\Http\Middleware\EnforceActiveSubscription;
use App\Modules\System\Subscription\Http\Middleware\EnforceFeature;
use App\Modules\System\Subscription\Http\Middleware\EnforceQuota;
use App\Modules\System\Superadmin\Http\Middleware\EnsureSuperadmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Package\Middleware\Authentication;
use Package\Middleware\Authorization;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
        $middleware->alias([
            'cors' => Cors::class,
            'json.response' => ForceJsonResponse::class,
            'auth.tera' => Authentication::class,
            'permission' => Authorization::class,
            'subscription.active' => EnforceActiveSubscription::class,
            'subscription.quota' => EnforceQuota::class,
            'subscription.feature' => EnforceFeature::class,
            'superadmin' => EnsureSuperadmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

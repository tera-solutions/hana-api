<?php

namespace App\Modules\Finance;

use App\Modules\CRM\Parent\Events\ParentCreated;
use App\Modules\Finance\Wallet\Models\Wallet;
use App\Modules\Finance\Wallet\Services\WalletService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider as Provider;

class ModuleProvider extends Provider
{
    public function boot()
    {
        $this->activate();
        $this->registerListeners();
    }

    /**
     * BR002: every new parent automatically gets a wallet (one per owner, BR001).
     */
    private function registerListeners(): void
    {
        Event::listen(ParentCreated::class, function (ParentCreated $event) {
            $parent = $event->model;

            app(WalletService::class)->createForOwner(
                (int) $parent->business_id,
                Wallet::OWNER_PARENT,
                (int) $parent->id,
            );
        });
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

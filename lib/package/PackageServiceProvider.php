<?php

namespace Package;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\File;

class PackageServiceProvider extends ServiceProvider
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
        try {
            DB::connection()->getPdo();

            // if (DB::connection()->getDatabaseName()) {
            //     // init service for module
            //     $this->registerDynamicModule();
            // }
            if (!$this->isDatabaseReady()) {
                return;
            }
            $this->registerDynamicModule();
        } catch (\Exception $exception) {
            Log::info($exception->getMessage());
        }
    }

    private function isDatabaseReady(): bool
    {
        try {
            return DB::connection()->getDatabaseName() !== null;
        } catch (\Throwable $e) {
            Log::warning($e->getMessage());
            return false;
        }
    }

    /**
     * Register services.
     * @return void
     */
    public function register()
    {
        try {
            DB::connection()->getPdo();

            if (DB::connection()->getDatabaseName()) {
                $this->deacvativeModule();
                // Register service for module
                $this->registerDynamicModule();

                $path_util = __DIR__ . "/Util/Utils.php";

                if (File::exists($path_util)) {
                    require_once $path_util;
                }
            }
        } catch (\Exception $exception) {
            //
        }
    }

    private function deacvativeModule()
    {
        try {
            $dir_path = app_path('/Modules');

            if (File::exists($dir_path)) {
                $list_folder = array_map('basename', File::directories($dir_path));

                foreach ($list_folder as $folder) {
                    $filename = "App\\Modules\\{$folder}\\ModuleProvider";

                    $instance = new $filename($this->app);

                    $instance->deactivate();
                }
            }
        } catch (\Exception $exception) {
            $message = $exception->getMessage();
            Log::emergency($message);
        }
    }

    private function registerDynamicModule()
    {
        try {
            $dir_path = __DIR__ . '/setting.php';
            $dir_path = app_path('/Modules');

            if (!File::isDirectory($dir_path)) {
                return;
            }

            if (File::exists($dir_path)) {
                $list_folder = array_map('basename', File::directories($dir_path));

                foreach ($list_folder as $folder) {
                    $filename = "App\\Modules\\{$folder}\\ModuleProvider";

                    if (class_exists($filename)) {
                        $this->app->register($filename);
                    } else {
                        Log::warning("ModuleProvider not found: {$filename}");
                    }
                }
            }
        } catch (\Exception $exception) {
            $message = $exception->getMessage();
            Log::emergency($message);
        }
    }
}

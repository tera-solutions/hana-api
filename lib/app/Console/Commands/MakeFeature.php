<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class MakeFeature extends Command
{
    protected $signature = 'make:feature 
        {module}
        {feature}
        {--init}
        {--api}
        {--migration}
        {--full}';

    protected $description = 'Generate full feature (PRO)';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $module = ucfirst($this->argument('module'));
        $feature = ucfirst($this->argument('feature'));

        $base = app_path("Modules/{$module}/{$feature}");

        $this->createFolders($base);
        $this->createModel($module, $feature, $base);
        $this->createService($module, $feature, $base);
        $this->createActions($module, $feature, $base);
        $this->createController($module, $feature, $base);
        $this->createRouter($module, $feature, $base);

        if ($this->option('api')) {
            $this->createRequests($module, $feature, $base);
            $this->createResource($module, $feature, $base);
        }

        if ($this->option('migration')) {
            $this->createMigration($feature);
        }

        if ($this->option('full')) {
            $this->createEvent($module, $feature, $base);
        }

        if ($this->option('init')) {
            $this->createModuleProvider($module);
            $this->initModuleRouter($module);
        }

        $this->info("🔥 Feature {$module}/{$feature} generated");
    }

    /* ================= FOLDERS ================= */

    private function createFolders($base)
    {
        foreach ([
            'Models',
            'Services',
            'Actions',
            'Router',
            'Http/Controllers',
            'Http/Requests',
            'Http/Resources',
            'Events',
            'Listeners'
        ] as $folder) {
            File::makeDirectory("{$base}/{$folder}", 0755, true, true);
        }
    }

    /* ================= MODEL ================= */

    private function createModel($module, $feature, $base)
    {
        $moduleKey = strtolower($module);
        $prefix = config("modules.$moduleKey.prefix") ?? $moduleKey;
        $table = strtolower(Str::plural(Str::snake($feature)));

        $tableName = $prefix . '_' . $table;

        File::put("{$base}/Models/{$feature}.php", <<<PHP
<?php

namespace App\Modules\\{$module}\\{$feature}\Models;

use Illuminate\Database\Eloquent\Model;

class {$feature} extends Model
{
    protected \$table = '{$tableName}';

    protected \$guarded = [];
}
PHP);
    }

    /* ================= SERVICE ================= */

    private function createService($module, $feature, $base)
    {
        File::put("{$base}/Services/{$feature}Service.php", <<<PHP
<?php

namespace App\Modules\\{$module}\\{$feature}\Services;

use App\Modules\\{$module}\\{$feature}\Models\\{$feature};

class {$feature}Service
{
    public function paginate()
    {
        return {$feature}::paginate();
    }

    public function find(\$id)
    {
        return {$feature}::findOrFail(\$id);
    }

    public function create(array \$data)
    {
        return {$feature}::create(\$data);
    }

    public function update(\$id, array \$data)
    {
        \$model = \$this->find(\$id);
        \$model->update(\$data);
        return \$model;
    }

    public function delete(\$id)
    {
        return \$this->find(\$id)->delete();
    }
}
PHP);
    }

    /* ================= ACTIONS ================= */

    private function createActions($module, $feature, $base)
    {
        $actions = [
            'Create' => 'create',
            'Update' => 'update',
            'Delete' => 'delete',
            'Get' => 'find',
            'List' => 'paginate',
        ];

        foreach ($actions as $name => $method) {
            File::put("{$base}/Actions/{$name}{$feature}Action.php", <<<PHP
<?php

namespace App\Modules\\{$module}\\{$feature}\Actions;

use App\Modules\\{$module}\\{$feature}\Services\\{$feature}Service;

class {$name}{$feature}Action
{
    public function handle(...\$params)
    {
        return app({$feature}Service::class)->{$method}(...\$params);
    }
}
PHP);
        }
    }

    /* ================= CONTROLLER ================= */

    private function createController($module, $feature, $base)
    {
        $plural = Str::kebab(Str::plural($feature));

        File::put("{$base}/Http/Controllers/{$feature}Controller.php", <<<PHP
<?php

namespace App\Modules\\{$module}\\{$feature}\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\\{$module}\\{$feature}\Actions\\Create{$feature}Action;
use App\Modules\\{$module}\\{$feature}\Actions\\Update{$feature}Action;
use App\Modules\\{$module}\\{$feature}\Actions\\Delete{$feature}Action;
use App\Modules\\{$module}\\{$feature}\Actions\\Get{$feature}Action;
use App\Modules\\{$module}\\{$feature}\Actions\\List{$feature}Action;
use App\Modules\\{$module}\\{$feature}\Http\Requests\\Create{$feature}Request;
use App\Modules\\{$module}\\{$feature}\Http\Requests\\Update{$feature}Request;

/**
 * @group {$module} - {$feature}
 */
class {$feature}Controller extends Controller
{
    public function list(List{$feature}Action \$action)
    {
        \$data = \$action->handle();
        return \$this->respondSuccess(\$data);
    }

    public function create(Create{$feature}Request \$request, Create{$feature}Action \$action)
    {
        \$data = \$action->handle(\$request->validated());
        return \$this->respondSuccess(\$data);
    }

    public function detail(\$id, Get{$feature}Action \$action)
    {
        \$data = \$action->handle(\$id);
        return \$this->respondSuccess(\$data);
    }

    public function update(Update{$feature}Request \$request, \$id, Update{$feature}Action \$action)
    {
        \$data = \$action->handle(\$id, \$request->validated());
        return \$this->respondSuccess(\$data);
    }

    public function delete(\$id, Delete{$feature}Action \$action)
    {
        \$data = \$action->handle(\$id);
        return \$this->respondSuccess(\$data);
    }
}
PHP);
    }

    /* ================= ROUTES ================= */

    protected function createRouter($module, $feature, $basePath)
    {
        $featureUri = \Illuminate\Support\Str::kebab($feature);

        $content = <<<PHP
<?php

use Illuminate\Support\Facades\Route;
use App\Modules\\$module\\$feature\Http\Controllers\\{$feature}Controller;

Route::prefix('$featureUri')->group(function () {
    Route::get('/list', [{$feature}Controller::class, 'list']);
    Route::get('/detail/{id}', [{$feature}Controller::class, 'detail']);
    Route::post('/create', [{$feature}Controller::class, 'create']);
    Route::put('/update/{id}', [{$feature}Controller::class, 'update']);
    Route::delete('/delete/{id}', [{$feature}Controller::class, 'delete']);

});
PHP;

        $this->files->put("$basePath/Router/api.php", $content);
    }

    /* ================= REQUEST ================= */

    private function createRequests($module, $feature, $base)
    {
        foreach (['Create', 'Update'] as $type) {
            File::put("{$base}/Http/Requests/{$type}{$feature}Request.php", <<<PHP
<?php

namespace App\Modules\\{$module}\\{$feature}\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam name string required
 */
class {$type}{$feature}Request extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
PHP);
        }
    }

    /* ================= RESOURCE ================= */

    private function createResource($module, $feature, $base)
    {
        File::put("{$base}/Http/Resources/{$feature}Resource.php", <<<PHP
<?php

namespace App\Modules\\{$module}\\{$feature}\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class {$feature}Resource extends JsonResource
{
    public function toArray(\$request)
    {
        return parent::toArray(\$request);
    }
}
PHP);
    }

    /* ================= MIGRATION ================= */

    private function createMigration($feature)
    {
        $table = Str::snake(Str::pluralStudly($feature));

        $this->call('make:migration', [
            'name' => "create_{$table}_table",
            '--create' => $table,
        ]);
    }

    /* ================= EVENT ================= */

    private function createEvent($module, $feature, $base)
    {
        File::put("{$base}/Events/{$feature}Created.php", <<<PHP
<?php

namespace App\Modules\\{$module}\\{$feature}\Events;

class {$feature}Created
{
    public function __construct(public \$model){}
}
PHP);
    }

    private function createModuleProvider($module)
    {
        $modulePath = app_path("Modules/{$module}");
        $providerPath = "{$modulePath}/ModuleProvider.php";

        if (File::exists($providerPath)) {
            return;
        }

        File::makeDirectory($modulePath, 0755, true, true);

        File::put($providerPath, <<<PHP
<?php

namespace App\Modules\\{$module};

use Illuminate\Support\ServiceProvider as Provider;
use Illuminate\Support\Facades\File;

class ModuleProvider extends Provider
{
    public function boot()
    {
        \$this->activate();
    }

    public function register()
    {
        \$this->validation();
    }

    public function activate()
    {
        try {
            \$this->loadResource();
        } catch (\\Exception \$e) {
            return;
        }
    }

    public function deactivate()
    {
        try {
            //
        } catch (\\Exception \$e) {
            return;
        }
    }

    public function loadResource()
    {
        if (File::exists(__DIR__ . '/routes.php')) {
            \$this->loadRoutesFrom(__DIR__ . '/routes.php');
        }
    }

    public function validation()
    {
        //
    }
}
PHP);

        $this->info("✔ ModuleProvider created: {$module}");
    }

    protected function initModuleRouter($module)
    {
        $moduleKey = strtolower($module);

        $prefix = config("modules.$moduleKey.prefix");

        if (!$prefix) {
            throw new \Exception("Module [$module] not found in config");
        }

        $modulePath = app_path("Modules/$module");
        $routerPath = $modulePath . '/routes.php';

        // Nếu đã tồn tại thì bỏ qua
        if ($this->files->exists($routerPath)) {
            $this->warn("Router already exists: $routerPath");
            return;
        }

        $content = <<<PHP
<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['api', 'cors', 'json.response'],
    'prefix' => env('API_VERSION') . '/$prefix'
], function () {

    \$featureDirs = File::directories(app_path('Modules/$module'));

    foreach (\$featureDirs as \$dir) {
        \$routeFile = \$dir . '/Router/api.php';

        if (File::exists(\$routeFile)) {
            require \$routeFile;
        }
    }
});
PHP;

        $this->files->put($routerPath, $content);

        $this->info("Module router created: $routerPath");
    }
}
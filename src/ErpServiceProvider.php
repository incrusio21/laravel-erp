<?php

namespace Erp;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use ReflectionClass;

class ErpServiceProvider extends ServiceProvider
{
    use Traits\Console;

    /**
     * Register the application's event listeners.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('erp', function ($app) {
            return new Erp($app->make('files'));
        });

        $this->app->singleton('sysdefault', function ($app) {
            return new SysDefault($app->make('files'));
        });

        $this->app->singleton('flags', function ($app) {
            return new Flags($app->make('cache')->get('flags') ?? []);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/erp.php', 'erp');
        $this->setup_module_map();

        Event::subscribe(Events\Subscribe::class);
        Facades\SysDefault::init();

        // Register the command if we are using the application via the CLI
        if ($this->app->runningInConsole()) {
            $this->loadConsole();
        }

        $this->registerRoutes();
    }

    protected function loadConsole()
    {
        $this->publishes([
            __DIR__.'/../config/erp.php' => config_path('erp.php'),
        ], 'config');

        $this->loadCommand(__NAMESPACE__, __DIR__.DS.'Command');
    }

    protected function registerRoutes()
    {
        Route::group([
            'prefix' => config('erp.prefix.api'),
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });

        Route::group([
            'prefix' => config('erp.prefix.desktop'),
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    protected function setup_module_map()
    {
        $erp = app('erp');

        $erp->app_modules = Cache::get("app_modules", []);
        $erp->module_app = Cache::get("module_app", []);
    
        if (!($erp->app_modules && $erp->module_app)){
            foreach ($erp->get_all_apps() as $app) {
                $app_name = $app['name'];
                $erp->app_modules[$app_name] = $erp->app_modules[$app_name] ?? [];
                foreach ($erp->get_module_list($app) as $module) {
                    $module = scrub($module, FALSE);
                    $erp->module_app[$module] = $app_name;
                    $erp->app_modules[$app_name][] = $module;
                }
            }
    
            Cache::forever("app_modules", $erp->app_modules);
            Cache::forever("module_app",  $erp->module_app);
        }
    }
}
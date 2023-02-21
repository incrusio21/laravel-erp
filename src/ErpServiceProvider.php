<?php

namespace Erp;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Finder\Finder;
use ReflectionClass;

class ErpServiceProvider extends ServiceProvider
{
    /**
     * Register the application's event listeners.
     */
    public function register() : void
    {
        $this->app->singleton('erp', function ($app) {
            return new Init($app->make('files'));
        });

        $this->app->singleton('flags', function ($app) {
            return new Flags($app->make('cache')->get('flags') ?? []);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot() : void
    {   
        $this->loadViewsFrom(__DIR__ . '/../views', 'erp');
        $this->mergeConfigFrom(__DIR__.'/../config/erp.php', 'erp');
        $this->mergeConfigFrom(__DIR__.'/../config/doctype.php', 'doctype');
        $this->setup_module_map();

        // Register the command if we are using the application via the CLI
        if ($this->app->runningInConsole()) {
            $this->loadConsole();
        }

        $this->registerRoutes();
    }

    /**
     * Registers the web routes for the Erp package.
     * The function is responsible for registering the web routes that are
     * associated with the Erp package. This function loads the web.php route file
     * and prefixes the routes with the desktop prefix specified in the Erp config file.
    */
    protected function registerRoutes() : void
    {
        // Route::group([
        //     'prefix' => config('erp.prefix.api'),
        // ], function () {
        //     $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        // });

        Route::group([
            'prefix' => config('erp.prefix.desktop'),
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    /**
     * Load console commands, views, and configuration files for the package.
    */
    protected function loadConsole() : void
    {
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/erp')
        ], 'views');

        $this->publishes([
            __DIR__.'/../config/erp.php' => config_path('erp.php'),
            __DIR__.'/../config/doctype.php' => config_path('doctype.php'),
        ], 'config');

        $paths = array_unique(Arr::wrap(__DIR__.DS.'Console'));
        
        foreach ((new Finder)->in($paths)->files() as $command) {
            $command = __NAMESPACE__.'\\'.str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($command->getRealPath(), realpath(__DIR__).DS)
            );
            
            if (is_subclass_of($command, Command::class) && ! (new ReflectionClass($command))->isAbstract()) {
                Artisan::starting(function ($artisan) use ($command) {
                    $artisan->resolve($command);
                });
            }
        }
    }
    
    /**
     * Set up the ERP module map by caching app modules and module app associations.
    */
    protected function setup_module_map() : void
    {
        $erp = app('erp');
        
        $erp->app_modules = Cache::get("app_modules", []);
        $erp->module_app = Cache::get("module_app", []);
        
        if (empty($erp->app_modules) || empty($erp->module_app)){
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
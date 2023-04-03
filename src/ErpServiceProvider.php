<?php

namespace LaravelErp;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;

class ErpServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        Foundation\Console\AddSiteCommand::class,
        Foundation\Console\GetAppCommand::class,
        Foundation\Console\InitCommand::class,
        Foundation\Console\InstallAppCommand::class,
        Foundation\Console\ListSiteCommand::class,
        Foundation\Console\NewAppCommand::class,
        Foundation\Console\StorageLinkCommand::class,
        Foundation\Console\UpdateEnvSiteCommand::class
    ];

    /**
     * Register the application's event listeners.
     */
    public function register() : void
    {
        $this->app->alias('artisan',\LaravelErp\Console\Application::class);

        $this->commands($this->commands);

        $this->app->singleton('laravel-erp', function ($app) {
            return new Init(
                $app->make('files'),
                $app->make('cache')->get('all_apps') ?? [],
                $app->make('cache')->get('app_modules') ?? [],
                $app->make('cache')->get('module_app') ?? [],
                $app->make('config')->get('erp')
            );
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
        $this->mergeConfigFrom(__DIR__.'/../config/site.php', 'site');
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
            __DIR__.'/../config/site.php' => config_path('site.php'),
        ], 'config');
    }

    protected function setup_module_map()
    {
        $erp = app('laravel-erp');
        
        if(empty($erp->all_apps)){
            Cache::forever("all_apps", $erp->get_all_apps()->all());
        }

        if (empty($erp->app_modules) || empty($erp->module_app)){
            foreach ($erp->all_apps as $app) {
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
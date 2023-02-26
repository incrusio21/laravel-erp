<?php

namespace Erp;

use Erp\Foundation\Console\AddSiteCommand;
use Erp\Foundation\Console\InitCommand;
use Erp\Foundation\Console\StorageLinkCommand;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use ReflectionClass;

class ErpServiceProvider extends ServiceProvider
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        AddSiteCommand::class,
        InitCommand::class,
        StorageLinkCommand::class,
    ];

    /**
     * Register the application's event listeners.
     */
    public function register() : void
    {
        $this->app->singleton('erp', function ($app) {
            return new Init(
                $app->make('files'),
                $app->make('cache')->get('app_modules') ?? [],
                $app->make('cache')->get('module_app') ?? []
            );
        });

        $this->app->alias('artisan',\Erp\Console\Application::class);

        $this->commands($this->commands);
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
        setup_module_map();

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
}
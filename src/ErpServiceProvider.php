<?php

namespace Erp;

use Erp\View\Components\Layout;
use Erp\Commands\MigrateCommand;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class ErpServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'erp');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->mergeConfigFrom(__DIR__.'/../config/erp.php', 'erp');

        // Register the command if we are using the application via the CLI
        if ($this->app->runningInConsole()) {
            $this->registerMigrations();
    
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/erp')
            ], 'views');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'sanctum-migrations');

            $this->publishes([
                __DIR__ . '/../resources/js' => resource_path('js'),
                __DIR__ . '/../resources/css' => resource_path('css'),
                __DIR__ . '/../package.json' => base_path('package.json'),
                __DIR__ . '/../tsconfig.json' => base_path('tsconfig.json'),
                __DIR__ . '/../vite.config.ts' => base_path('vite.config.ts'),
            ], 'assets');

            $this->publishes([
                __DIR__.'/../config/erp.php' => config_path('erp.php'),
            ], 'config');

            $this->commands([
                MigrateCommand::class,
            ]);
        }

        \Blade::component('layout', Layout::class);

        \Vite::useBuildDirectory('resource');

        $this->registerRoutes();
    }
    
    /**
     * Register Sanctum's migration files.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        // if (Sanctum::shouldRunMigrations()) {
            return $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // }
    }

    protected function registerRoutes()
    {
        Route::group([
            'prefix' => config('erp.route.api.prefix'),
            'middleware' => config('erp.route.middleware'),
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/routes/api.php');
        });

        Route::group([
            'prefix' => config('erp.route.web.prefix'),
            'middleware' => config('erp.route.middleware'),
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        });
    }
}

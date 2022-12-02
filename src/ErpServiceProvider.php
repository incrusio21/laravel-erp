<?php

namespace Erp;

use Erp\View\Components\Layout;
use Erp\Hooks\DocEvent;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Finder\Finder;
use ReflectionClass;

class ErpServiceProvider extends ServiceProvider
{
    /**
     * Register the application's event listeners.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('doc_event', function ($app) {
            return (new DocEvent($app))->setQueueResolver(function () use ($app) {
                return $app->make(QueueFactoryContract::class);
            });
        });
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
        // $this->mergeConfigFrom(__DIR__.'/../config/hooks.php', 'hooks');

        // $this->loadHooks();

        // Register the command if we are using the application via the CLI
        if ($this->app->runningInConsole()) {
            $this->loadConsole();
        }

        \Blade::component('layout', Layout::class);

        \Vite::useBuildDirectory('resource');

        $this->registerRoutes();

        // print_r(hooks('erp', []));
    }
    
    protected function loadConsole()
    {
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

        $this->loadCommand();
    }

    /**
     * Load the given configuration with the existing configuration.
     *
     * @return void
     */
    protected function loadHooks()
    {
        if(\File::exists($installed_path = config('erp.app.installed_app'))) {
            $installed_list = json_decode(\File::get($installed_path));
            $hooks = $this->app->make('hooks');

            foreach($installed_list->autoload->{"psr-4"} as $namespace => $path){
                
                $hooks->set($key, array_merge(
                    require base_path($path).'/hooks.php', $hooks->get($key, [])
                ));
            } 
        }
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

    /**
     * Register all of the commands in the given directory.
     *
     * @param  array|string  $paths
     * @return void
     */
    protected function loadCommand()
    {
        $paths = array_unique(Arr::wrap(__DIR__.'/Console'));

        $namespace = __NAMESPACE__.'\\';

        foreach ((new Finder)->in($paths)->files() as $command) {
            $command = $namespace.str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($command->getRealPath(), realpath(__DIR__).DIRECTORY_SEPARATOR)
            );

            if (is_subclass_of($command, Command::class) && ! (new ReflectionClass($command))->isAbstract()) {
                Artisan::starting(function ($artisan) use ($command) {
                    $artisan->resolve($command);
                });
            }
        }
    }
}

<?php

namespace Erp\Traits;

use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use ReflectionClass;

trait Console {
    /**
     * Register all of the commands in the given directory.
     *
     * @param  array|string  $paths
     * @return void
     */
    protected function loadCommand($namespace, $path)
    {
        $paths = array_unique(Arr::wrap($path));
        
        foreach ((new Finder)->in($paths)->files() as $command) {
            $command = $namespace.'\\'.str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($command->getRealPath(), realpath(__DIR__.DS.'..').DS)
            );
            
            if (is_subclass_of($command, Command::class) && ! (new ReflectionClass($command))->isAbstract()) {
                Artisan::starting(function ($artisan) use ($command) {
                    $artisan->resolve($command);
                });
            }
        }
    }

}
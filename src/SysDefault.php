<?php

namespace Erp;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;

class SysDefault
{
    /**
     * The ERP installed app.
     *
     * @var array
     */
    protected $installed_app = [];

    /**
     * Create a new migration install command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Support\Composer  $composer
     * @return void
     */
    public function __construct(Filesystem $files = null)
    {   
        $this->files = $files ?: new Filesystem;
    }

    public function getInstalledApp($key = null)
    {
        return Arr::get($this->installed_app, strtolower($key), []);
    }

    protected function init()
    {
        $this->setInstalledApp();
    }
    
    protected function setInstalledApp()
    {
        // $this->installed_app += $this->defaultApp();
    }
    
    public function __call(string $method, array $parameters)
    {
        return $this->$method(...$parameters);
    }

    public static function __callStatic(string $method, array $parameters) {
        // Note: value of $name is case sensitive.
        return (new static)->$method(...$parameters);   
    }
}
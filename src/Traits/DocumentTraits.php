<?php

namespace Erp\Traits;

use Erp\Models\App;
use InvalidArgumentException;

trait DocumentTraits {

    /**
     * Register a view extension with the finder.
     *
     * @var string[]
     */
    protected $extensions = ['php'];

    protected function getInstalledApp($name = null)
    {
        $app_name = App::select('name')->find($name);
        
        $installed_app = [];
        $installed_list = json_decode($this->files->get(base_path($this->app_file)));        
        foreach($installed_list->autoload->{"psr-4"} as $namespace => $path){
            // cek jika module yang ingin d install ada atau tidak
            if(!$this->files->exists($setup_path = base_path($this->erp_path.str_replace('src','',$path).'setup.json'))) {
                continue;
            }

            $setup = json_decode($this->files->get($setup_path));

            $installed_app += [ $setup->name => [$this->erp_path.$path => $namespace] ];
        } 

        // installing app pada database
        foreach (array_merge(
            ['erp' => [ __DIR__.'/../' => 'Erp']], 
            ['app' => config('erp.module')],
            $installed_app
        ) as $app => $modules) {
            if($app_name->name == $app){
                return $modules;
            }
        }

        throw new InvalidArgumentException("App [{$app_name}] not found.");
    }

    protected function findInPaths($name, $paths)
    {
        foreach ((array) $paths as $path => $namespace) {
            // print_r($path);
            foreach ($this->getPossibleFiles($name) as $file) {
                if ($this->files->exists($methodPath = $path.$file)) {
                    return [$namespace, $methodPath];
                }
            }
        }
        // return erpThrow("[$file] tidak ditemukan', 'File Not Found");

        throw new InvalidArgumentException("File [{$name}] not found.");
    }

    protected function getPossibleFiles($name)
    {
        return array_map(fn ($extension) => $name.'.'.$extension, $this->extensions);
    }

    protected function getpossibleFunction($method, $args = []){
        if (function_exists($method)) {
            return  call_user_func_array($method, $args);
        }

        throw new InvalidArgumentException("Function [{$method}] not found.");
    }
}
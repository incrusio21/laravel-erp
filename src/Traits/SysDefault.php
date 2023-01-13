<?php

namespace Erp\Traits;

use Erp\Models\Module;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

trait SysDefault {

    protected $installed_app = [];

    /**
     * The ERP app name on laravel namespace.
     *
     * @var string
     */
    protected $app_name;

    /**
     * The ERP module name on laravel app and installed app.
     *
     * @var string
     */
    protected $modules;

    /**
     * The ERP folder path on installed app.
     *
     * @var string
     */
    protected $erp_path;

    /**
     * The ERP Composer folder path.
     *
     * @var string
     */
    protected $app_file;

    /**
     * Register a view extension with the finder.
     *
     * @var string[]
     */
    protected $extensions = ['php'];

    public function defaultApp(){
        return [
            'erp' => [ 'path' => __DIR__.DS.'..', 'namespace' =>  'Erp\\'],
            $this->app_name => [ 'path' => app_path(), 'namespace' => app()->getNamespace() ]
        ];
    }

    public function getAppTable($key){
        return Arr::get($this->app_table, $key, null);
    }

    public function getAppName(){
        return $this->app_name;
    }

    public function getAppFile(){
        return $this->app_file;
    }

    public function getAppPath(){
        return $this->erp_path;
    }

    public function getAppModule($key){
        return Arr::get($this->modules, $key, null);
    }

    public function getInstalledApp($key = null)
    {
        return $key ? Arr::get($this->installed_app, $key, []) : $this->installed_app;
    }

    public function getInstalledModule(){
        return Module::whereIn('app', array_keys($this->installed_app))->get();
    }

    public function require_method($name, $methods)
    {
        $path = explode('.', $name);

        [$namespace, $methodPath] = $this->findInPaths(
            implode(DS, $file = array_slice($path, 1)), $this->getInstalledApp($path[0])
        );
        // Filesystem
        // getRequire($path, array $data = [])
        require $methodPath;
        $method = $namespace;

        if(sizeof($without_name = array_slice($file, 0, -1, true)) > 0) 
            $method .= implode('\\', $without_name);
        
        if(is_array($methods)){
            $calback_array = [];
            foreach ($methods as $value) {
                $calback = $method.'\\'.$value;
                array_push($calback_array, $this->getpossibleFunction($calback, $value));
            }  
            return $calback_array;
        }else{
            $calback = $method.'\\'.$methods;
            return $this->getpossibleFunction($calback, $methods);
        }
    }

    public function findInPaths($name, $installed_app)
    {
        foreach ($this->getPossibleFiles($name) as $file) {
            if (File::exists($methodPath = $installed_app['path'].DS.$file)) {
                return [$installed_app['namespace'], $methodPath];
            }
        }
        // return erpThrow("[$file] tidak ditemukan', 'File Not Found");

        throw new InvalidArgumentException("File [{$name}] not found.");
    }

    public function getPossibleFiles($name)
    {
        return array_map(fn ($extension) => $name.'.'.$extension, $this->extensions);
    }
    
    public function getpossibleFunction($method, $name = null){
        if (function_exists($method)) {
            return $method;
        }

        throw new InvalidArgumentException("Function [".($name ?: $method)."] not found.");
    }

    protected function setErp_app(Array $conf){
        if(!array_key_exists('app', $conf) && !array_key_exists('table', $conf)){
            throw new InvalidArgumentException("File cofigurasi ERP tidak memiliki Variable [app / table].");
        }

        $erp_app = $conf['app'];
        // get erp app name n modules
        $this->modules = $erp_app['modules'];
        $this->app_name = $erp_app['name'];
        // get erp installed app path n file
        $erp_app['path'] && $this->erp_path = $erp_app['path'].DS;
        $this->app_file = $this->erp_path.$erp_app['filename'];
        // get nama table default erp
        $this->app_table = $conf['table'];
    }
}
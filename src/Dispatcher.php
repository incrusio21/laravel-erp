<?php

namespace Erp;

use Erp\Models\App;
use Erp\Traits\DocumentTraits;
use Erp\Traits\ErpTraits;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class Dispatcher
{
    use DocumentTraits;

    protected $installed_app = [];

    protected $form_list = [];

    /**
     * The ERP folder path.
     *
     * @var string
     */
    protected $app_name;

    /**
     * The ERP folder path.
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
     * The ERP Composer folder path.
     *
     * @var string
     */
    protected $modules;

    public function init()
    {
        $this->setErp_app(config('erp.app'));

        $this->app_table = config('erp.table');
            
        if (Schema::hasTable($this->getAppTable('app'))) {
            $this->setInstalledApp();
        }
    }

    public function defaultApp($is_module = false){
        return [
            'erp' => [ __DIR__.($is_module ? DS.'Http' : '') => 'Erp\\'.($is_module ? 'Http\\' : '')],
            $this->app_name => [ app_path($this->modules['laravel_app']) => app()->getNamespace().$this->modules['laravel_app'].'\\']
        ];
    }
    
    public function setInstalledApp()
    {
        $installed_app = $this->defaultApp();

        if(File::exists(base_path($this->app_file))){
            $installed_list = json_decode(File::get(base_path($this->app_file)));
            foreach($installed_list->autoload->{"psr-4"} as $namespace => $path){
                // cek jika module yang ingin d install ada atau tidak
                if(!File::exists($setup_path = base_path($this->erp_path.str_replace('src','',$path).'setup.json'))) {
                    continue;
                }
    
                $setup = json_decode(File::get($setup_path));
    
                $installed_app += [ $setup->name => [ $this->erp_path.$path => $namespace] ];
            }
        }

        $app_name = App::select('name')->get();

        foreach ($app_name as $value) {
            if(array_key_exists($value->name, $this->installed_app))  continue;

            if(array_key_exists($value->name, $installed_app)){
                $this->installed_app += [$value->name => $installed_app[$value->name] ];
            }
        }
    }

    public function getInstalledApp($key = null)
    {
        return $key ? Arr::get($this->installed_app, $key, []) : $this->installed_app;
    }
    
    /**
     * @return null
     */
    function doctpye_form($calback = false)
    {   
        $installed_app = [];
        if($this->files->exists($installed_path = config('erp.app.installed_app'))) {
            $installed_list = json_decode($this->files->get($installed_path));
            foreach($installed_list->autoload->{"psr-4"} as $namespace => $path){
                $installed_app += [
                    base_path($path).'/Http' => $namespace.'Http'
                ];
            } 
        }
        
        $list_app = array_merge([ __DIR__.'/Http' => 'Erp\Http'], config('erp.module'), $installed_app);
        foreach ($list_app as $path => $value) {
            // skip jika folder tidak d temukan
            if(!$this->files->exists($path.'/modules.txt')) {
                continue;
            }

            $modules_list = explode("\r\n", $this->files->get($path.'/modules.txt'));
            foreach ($modules_list as $key => $name){
                $module_path = $path.DS.str_replace(' ', '', $name).DS.'Controller';
                if(!$this->files->exists($module_path)) {
                    continue;
                }
                
                foreach (scandir($module_path) as $modules) {
                    // skip untuk path '.' atau '..'
                    if ($modules === '.' || $modules === '..') continue;
                    $module = $module_path.DS.$modules;

                    $this->form_json($modules, $module, $calback);
                }
            }

        }

        return $this->form_list;
    }
    
    /**
     * @param string $doc nama document 
     * @param string $app path domuent 
     * @param bool|function $calback fungsi calback jika ada
     * 
     * @return boolean|undefined
     */
    function form_json($doc, $app, $calback = false){
        // jika file form json ada masukkan dalam array
        if($this->files->exists($form = $app.DS.'form.json')){
            // baca meta modul
            if(is_callable($calback)){
                $data = $calback($doc, $form, config('erp.route.web.prefix'));
                if(is_array($data)){
                    $this->form_list += $data;
                }
            }

            return true;
        }
    }

    function call_method($name, Array $args = [], $document = null){
        if($document){
            throw new InvalidArgumentException("Mohon Bersabar, Ini Ujian. Fungsi belum selesai");
        }

        $path = explode('.', $name);

        [$namespace, $methodPath] = $this->findInPaths(
            implode(DS, $file = array_slice($path, 1, -1, true)), $this->getInstalledApp($path[0])
        );

        require $methodPath;
        
        $method = $namespace;

        if(sizeof($without_name = array_slice($file, 0, -1, true)) > 0) 
            $method .= '\\'.implode('\\', $without_name);
        
        $method .= '\\'.end($path);

        return $this->getpossibleFunction($method, $args ?: []);
    }

    public function getAppTable($key){
        return Arr::get($this->app_table, $key, null);
    }

    public function getAppFile(){
        return $this->app_file;
    }

    public function getAppPath(){
        return $this->erp_path;
    }

    protected function setErp_app($erp_app){
        $erp_app['path'] && $this->erp_path = $erp_app['path'].DS;
        $this->app_file = $this->erp_path.$erp_app['filename'];
        $this->modules = $erp_app['modules'];
        $this->app_name = $erp_app['name'];
    }
}
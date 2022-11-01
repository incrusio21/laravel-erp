<?php

namespace Erp;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ErpForm
{
    protected $form_list = [];
    /**
     * @var array<int, class-string<\Illuminate\Console\Command>>
     */
    public const DS = DIRECTORY_SEPARATOR;

    /**
     * @return null
     */
    protected function doctpye_form($calback = false)
    {   
        $installed_app = [];
        if(\File::exists($installed_path = config('erp.app.installed_app'))) {
            $installed_list = json_decode(\File::get($installed_path));
            foreach($installed_list as $path => $namespace){
                $installed_app += [
                    base_path($path) => $namespace.'Http'
                ];
            } 
        }

        $list_app = array_merge([ __DIR__.'/Http' => 'Erp\Http'], config('erp.app.module'), $installed_app);
        foreach ($list_app as $path => $value) {
            // skip jika folder tidak d temukan
            if(!\File::exists($path.'/modules.txt')) {
                continue;
            }

            $modules_list = explode("\r\n", \File::get($path.'/modules.txt'));
            foreach ($modules_list as $key => $name){
                $module_path = $path.self::DS.str_replace(' ', '', $name);
                if(!\File::exists($module_path)) {
                    continue;
                }
                
                foreach (scandir($module_path) as $modules) {
                    // skip untuk path '.' atau '..'
                    if ($modules === '.' || $modules === '..') continue;
                    $module = $module_path.self::DS.$modules;

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
        if(\File::exists($form = $app.self::DS.'form.json')){
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

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->$method(...$parameters);
    }
    
    /**
     * Handle dynamic static method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        if (in_array($method, ['doctpye_form'])) {
            return (new static)->$method(...$parameters);   
        }
    }
}
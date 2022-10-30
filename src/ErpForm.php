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
        $list_app = array_merge([ __DIR__.'/Http/Core' => '/Erp/Core'], config('erp.app.module'));
        foreach ($list_app as $path => $value) {
            // skip jika folder tidak d temukan
            if(!\File::exists($path)) {
                continue;
            }

            foreach (scandir($path) as $modules) {
                // skip untuk path '.' atau '..'
                if ($modules === '.' || $modules === '..') continue;
                $module = $path.self::DS.$modules;

                if($this->form_json($modules, $module, $calback)) continue;

                if (is_dir($module)) {
                    foreach (scandir($module) as $docType) {
                        if ($docType === '.' || $docType === '..') continue;
                        // dapatkan json modul
                        $this->form_json($docType, $module.self::DS.$docType, $calback);
                    }
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
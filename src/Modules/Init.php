<?php

namespace Erp\Modules;

class Init
{
    protected $doctype_php_modules = [];

    protected function load_doctype_module($doctype, $module=Null, $prefix="", $suffix="")
    {
        // Returns the module object for given doctype.
        if (!$module){
            $module = $this->get_doctype_module($doctype);
        }

        $app = $this->get_module_app($module);
        $key = serialize([$app, $doctype, $prefix, $suffix]);

        $module_name = $this->get_module_name($doctype, $module, $prefix, $suffix);

        if (!array_key_exists($key, $this->doctype_php_modules)) {
            $this->doctype_php_modules[$key] = $module_name;
        }
        
        return  $this->doctype_php_modules[$key];
    }

    protected function get_module_name($doctype, $module, $prefix = "", $suffix = "", $app = null)
    {
        $app = $app ?? $this->get_module_app($module);
        return "\\{$app}\\{$module}\\Controllers\\{$doctype}\\{$prefix}Controller{$suffix}";
    }

    protected function get_doctype_module($doctype)
    {
        // Returns **Module Def** name of given doctype.
        return Cache::rememberForever("doctype_modules", function () {
            return collect(
                DB::doc('Doctype')->select('name', 'module')->get()->mapWithKeys(function ($item) {
                    return [$item['name'] => $item['module']];
                })
            );
        })[$doctype];
    }

    protected function get_module_app($module)
    {
        return app('erp')->module_app[scrub($module, FALSE)];
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
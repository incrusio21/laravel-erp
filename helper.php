<?php

use Erp\Facades\SysDefault;
use Erp\Foundation\Migrate;
use Erp\Foundation\Utils;
use Erp\Models\Document;

define('DS', DIRECTORY_SEPARATOR);

if (! function_exists('new_doc')){
    function new_doc(string $docType)
    {
        return Document::doc($docType); 
    }
}

if (! function_exists('doc_value')){
    function doc_value(string $docType)
    {
        return Document::doc($docType); 
    }
}

if (! function_exists('erp')){
    function erp(string $function, $args)
    {
        if (is_string($args)) {
            $args = [$args];
        }
        
        return app('erp')->$function(...$args);
    }
}

if (! function_exists('migrate')){
    function migrate(Object $cont)
    {
        return (new Migrate)->create_or_update_table($cont); 
    }
}

if (! function_exists('sysdefault')){
    function sysdefault($function, $args = [])
    {
        if(method_exists(app('sysdefault'), $function) 
            && is_callable(array(app('sysdefault'), $function))){
            return app('sysdefault')->$function(...$args);
        }   
    }
}

if (! function_exists('flags')){
    function flags($fields, $value = null)
    {
        if(is_array($value)){
            return app('flags')->set($fields, $value);
        }

        return app('flags')->get($fields, $value);
    }
}

// Returns sluggified string. e.g. `Sales Order` becomes `Sales_Order` or `sales_order` if slug true
if (! function_exists('scrub')){
    function scrub($txt, $slug = TRUE) {
        $scrub = str_replace("-", "_", str_replace(" ", "_", $txt));
        return $slug ? strtolower($scrub) : $scrub;
    }
}

// Returns titlified string. e.g. `sales_order` becomes `Sales Order`.
if (! function_exists('unscrub')){
    function unscrub($txt) {
        return ucwords(str_replace("-", " ", str_replace("_", " ", $txt)));
    }
}
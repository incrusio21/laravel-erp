<?php

define('DS', DIRECTORY_SEPARATOR);
define('VARCHAR_LEN', 140);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Generate a hash string for the given text with additional random data and a timestamp.
 * 
 * @param string|null $txt The text to be hashed (default: null).
 * @param int|null $length The length of the output hash (default: null).
*/
if (! function_exists('generate_hash')){   
   function generate_hash($txt = null, $length = null) : string
   {
       $digest = Hash::make($txt . time() . Str::random(8));
       if ($length) {
           return substr($digest, 0, $length);
       }
       
       return $digest;
   }
}

/**
 * Create or update table with migrations.
 * 
 * @param object $cont
*/
if (! function_exists('migrate')){
    function migrate(object $cont) : void
    {
        (new \LaravelErp\Foundation\Migrate)->create_or_update_table($cont); 
    }
}

/**
 * Returns sluggified string. e.g. Sales Order becomes Sales_Order or sales_order if $slug true.
 * 
 * @param string $txt
 * @param bool $slug
*/
if (! function_exists('scrub')){
    function scrub($txt, $slug = TRUE) : string
    {
        $scrub = str_replace(" ", "-", $txt);
        return $slug ? str_replace("-", "_", strtolower($scrub)) : $scrub;
    }
}

/**
 * Returns titlified string. e.g. sales_order becomes Sales Order.
 * 
 * @param string $txt
*/
if (! function_exists('unscrub')){
    function unscrub($txt) : string
    {
        return ucwords(str_replace("-", " ", str_replace("_", " ", $txt)));
    }
}

/**
 * Set up the ERP module map by caching app modules and module app associations.
 * 
 * @param bool $reset
*/
if (! function_exists('setup_module_map')){
    function setup_module_map(bool $reset = false) : void
    {
        $erp = app('erp');
        
        if (empty($erp->app_modules) || empty($erp->module_app) && !$reset){
            foreach ($erp->get_all_apps() as $app) {
                $app_name = $app['name'];
                $erp->app_modules[$app_name] = $erp->app_modules[$app_name] ?? [];
                foreach ($erp->get_module_list($app) as $module) {
                    $module = scrub($module, FALSE);
                    $erp->module_app[$module] = $app_name;
                    $erp->app_modules[$app_name][] = $module;
                }
            }

            Cache::forever("app_modules", $erp->app_modules);
            Cache::forever("module_app",  $erp->module_app);
        }
    }
}


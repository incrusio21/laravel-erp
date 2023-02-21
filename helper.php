<?php

define('DS', DIRECTORY_SEPARATOR);
define('VARCHAR_LEN', 140);

if (! function_exists('migrate')){
    function migrate(Object $cont)
    {
        return (new \Erp\Foundation\Migrate)->create_or_update_table($cont); 
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

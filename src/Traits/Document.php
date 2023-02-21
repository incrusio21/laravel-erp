<?php

namespace Erp\Traits;

use Erp\Foundation\BaseDocument;
use Erp\Models\DB;
use Erp\Modules\Init;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Exception;

trait Document 
{
    protected function _get_controller($doctype)
    {
        if (Schema::hasTable('tab_doctype')) {
            $db_doc = DB::doc('Doctype')->select('name', 'custom')->find($doctype);
        }

        $module_name = $db_doc->module ?? 'Core';
        $custom = $db_doc->custom ?? false;

        if($custom){

        }else{                
            $_class = Init::load_doctype_module($doctype, $module_name);
            if (!is_subclass_of($_class, BaseDocument::class)) {
                throw new Exception("Class {$_class} does not extend " . BaseDocument::class);
            }
        }

        return $_class;
    }

    protected function get_controller($doctype)
    {
        if(!property_exists(app('erp')->controllers, 'site_controllers')){
            app('erp')->controllers->site_controllers = []; // frappe.controllers.setdefault(frappe.local.site, {})
        }

        if (!array_key_exists($doctype, app('erp')->controllers->site_controllers)) {
            app('erp')->controllers->site_controllers[$doctype] = $this->_get_controller($doctype);
        }

        $contoller = app('erp')->controllers->site_controllers[$doctype];
        return new $contoller;
    }

    protected function load_doctype_from_file($doctype) {
        $fname = scrub($doctype, FALSE);
        $file = __DIR__.DS.'..'.DS.'Core'.DS.'Controllers'.DS.$fname.DS.'form.json';
        // app_path("Core/Doctype/{$fname}/{$fname}.json");
        if (File::exists($file)) {
            $txt = json_decode(File::get($file), true);
    
            $fields = [];
            foreach ($txt['fields'] as $field) {
                $field['doctype'] = 'DocField';
                $fields[] = $field;
            }
    
            $permissions = [];
            if (array_key_exists('permissions', $txt)) {
                foreach ($txt['permissions'] as $perm) {
                    $perm['doctype'] = 'DocPerm';
                    $permissions[] = $perm;
                }
            }

            $txt['fields'] = array_map(function($d) {
                return (new BaseDocument)($d);
            }, $txt['fields']);
            // $txt['permissions'] = $permissions;
            return $txt;
        } else {
            throw new Exception("{$doctype} not found");
        }
    }

    protected function filter($data, $filters, $limit = null) 
    {
        $out = $_filters = [];

        if (empty($data)) {
            return $out;
        }

        // Setup filters as tuples
        if ($filters) {
            foreach ($filters as $f => $fval) {
                if (!is_array($fval)) {
                    if ($fval === true) {
                        $fval = ["not None", $fval];
                    } elseif ($fval === false) {
                        $fval = ["None", $fval];
                    } elseif (is_string($fval) && Str::startsWith($fval, '^')) {
                        $fval = ["^", Str::substr($fval, 1)];
                    } else {
                        $fval = ["=", $fval];
                    }
                }
                $_filters[$f] = $fval;
            }
        }

        foreach ($data as $d) {
            foreach ($_filters as $f => $fval) {
                if (!$this->compare(data_get($d, $f), $fval[0], $fval[1])) {
                    break;
                }else{
                    $out[] = $d;
                    if ($limit && count($out) >= $limit) {
                        break;
                    }
                }
            }
        }

        return $out;
    }

    protected function compare($value, $operator, $reference)
    {
        switch ($operator) {
            case "not None":
                return !is_null($value);
            case "None":
                return is_null($value);
            case "^":
                return Str::startsWith($value, $reference);
            case "in":
                return in_array($value, $reference);
            case "not in":
                return !in_array($value, $reference);
            default:
                return $value == $reference;
        }
    }
}
<?php

namespace LaravelErp\Traits;

use LaravelErp\Modules\BaseDocument;
use LaravelErp\Models\DB;
use LaravelErp\Modules\Init;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Exception;

trait Document 
{
    /**
     * Get the controller class for a given doctype.
     *
     * @param string $doctype The name of the doctype.
     * @throws Exception If the module class does not extend BaseDocument.
     * @return string The name of the controller class.
    */
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

    /**
     * Get the controller for a given doctype.
     * 
     * @param string $doctype The name of the doctype for which to get the controller.
     * @return mixed The controller instance for the given doctype.
    */
    protected function get_controller($doctype)
    {
        if(!property_exists(app('laravel-erp')->controllers, 'site_controllers')){
            app('laravel-erp')->controllers->site_controllers = []; // frappe.controllers.setdefault(frappe.local.site, {})
        }

        if (!array_key_exists($doctype, app('laravel-erp')->controllers->site_controllers)) {
            app('laravel-erp')->controllers->site_controllers[$doctype] = $this->_get_controller($doctype);
        }

        $contoller = app('laravel-erp')->controllers->site_controllers[$doctype];
        return new $contoller;
    }

    /**
     * Load a doctype's metadata from a JSON file in the Core Controllers directory.
     * 
     * @param string $doctype The name of the doctype to load.
     * @throws Exception If the doctype file does not exist.
     * @return array The doctype's metadata as an associative array.
    */
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

    /**
     * Filter data based on given filters.
     * 
     * @param array $data The data to filter.
     * @param array $filters The filters to apply.
     * @param int|null $limit Maximum number of items to return.
     * @return array The filtered data.
    */
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

    /**
     * Compares a given value with a reference based on a specified operator.
     * 
     * @param mixed $value The value to compare.
     * @param string $operator The operator to use in the comparison.
     * @param mixed $reference The reference value to compare against.
     * @return bool Returns true if the comparison succeeds, false otherwise.
    */
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
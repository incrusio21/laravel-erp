<?php

namespace Erp\Controllers;

use Erp\Foundation\Utils;
use Erp\Foundation\BaseDocument;
use Erp\Models\DB;

class Document
{
    protected function _get_controller($doctype)
    {
        $db_doc = DB::doc('Doctype')->select('name', 'custom')->find($doctype);

        $module_name = $db_doc->module ?? 'Core';
        $custom = $db_doc->custom ?? false;

        if($custom){

        }else{                
            $_class = Utils::load_doctype_module($doctype, $module_name);
            if (!is_subclass_of($_class, BaseDocument::class)) {
                throw new \Exception("Class {$class} does not extend " . BaseDocument::class);
            }
        }

        return $_class;
    }

    protected function get_controller($doctype)
    {
        $site_controllers = []; // frappe.controllers.setdefault(frappe.local.site, {})
        if (!array_key_exists($doctype, $site_controllers)) {
            $site_controllers[$doctype] = $this->_get_controller($doctype);
        }

        return $site_controllers[$doctype];
    }

    protected function get_doc($kwargs, ...$args) {
        if($kwargs instanceof BaseDocument){
            return $kwargs;
        }

        if ($args){
            if(is_string($kwargs)){
                $doctype = $kwargs;
            }else{
                throw new \Exception("First non keyword argument must be a string or dict");
            }
        }

        if (count($args) < 1 && is_array($kwargs)){
            if(array_key_exists("doctype", $kwargs)){
                $doctype = $kwargs["doctype"];
            }else{
                throw new \Exception('"doctype" is a required key');
            }
        }

        $controller = $this->get_controller($doctype);
        if ($controller){
            return new $controller($kwargs, ...$args);
        }
    }

    protected function new_doc($doctype, $parent_doc = null, $parentfield = null, $as_dict = false) {
        if (!array_key_exists($doctype, app('erp')->new_doc_templates)) {
            app('erp')->new_doc_templates[$doctype] = $this->make_new_doc($doctype);
        }
    
        $doc = clone app('erp')->new_doc_templates[$doctype];
    
        // set_dynamic_default_values($doc, $parent_doc, $parentfield);
    
        if ($as_dict) {
            return $doc;
        } else {
            return $this->get_doc($doc);
        }
    }

    protected function make_new_doc($doctype) {
        $doc = $this->get_doc([
            "doctype" => $doctype,
            "__islocal" => true,
            "docstatus" => 0
        ]);
    
        // set_user_and_static_default_values($doc);
    
        // $doc->fix_numeric_types();
        // $doc = $doc->get_valid_dict(false);
        $doc->doctype = $doctype;
        $doc->__islocal = true;
    
        // if (!$GLOBALS['meta']->is_single($doctype)) {
        //     $doc["__unsaved"] = 1;
        // }
    
        return $doc;
    }

    // def make_new_doc(doctype):
    //     doc = frappe.get_doc(
    //         {"doctype": doctype, "__islocal": 1, "owner": frappe.session.user, "docstatus": 0}
    //     )

    //     set_user_and_static_default_values(doc)

    //     doc._fix_numeric_types()
    //     doc = doc.get_valid_dict(sanitize=False)
    //     doc["doctype"] = doctype
    //     doc["__islocal"] = 1

    //     if not frappe.model.meta.is_single(doctype):
    //         doc["__unsaved"] = 1

    //     return doc

    public function __call(string $method, array $parameters)
    {
        return $this->$method(...$parameters);
    }

    public static function __callStatic(string $method, array $parameters) {
        // Note: value of $name is case sensitive.
        return (new static)->$method(...$parameters);   
    }
}

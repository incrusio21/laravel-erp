<?php

namespace Erp;

use Erp\Foundation\BaseDocument;
use Erp\Foundation\Meta;
use Erp\Models\Single;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Exception;

class Init
{
    use Traits\Document;

    public $__version__ = "1.0.0";

    public $app_modules;

    public $module_app;

    public $new_doc_templates = [];

    /**
     * Create a new migration install command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     */
    public function __construct(Filesystem $files = null)
    {   
        $this->files = $files ?: new Filesystem;
        $this->controllers = (object) [];

        $this->flags = new \Erp\Contracts\Flags;
        $this->local = (object) [
            'valid_columns' => []
        ];
    }

    /**
     * Generate a hash string for the given text with additional random data and a timestamp.
     * 
     * @param string|null $txt The text to be hashed (default: null).
     * @param int|null $length The length of the output hash (default: null).
    */
    public function generate_hash($txt = null, $length = null) : string
    {
        $digest = Hash::make($txt . time() . Str::random(8));
        if ($length) {
            return substr($digest, 0, $length);
        }
        
        return $digest;
    }

    /**
     * Get a list of all the installed applications
     * 
    */
    public function get_all_apps() : array //The list of all installed applications
    {
        $apps = [];
        
        // get erp path dan filename
        if($this->files->exists(config('erp.path').DS.config('erp.app'))) {
            
        }

        array_push($apps, ['name'=> 'Erp', 'path' => __DIR__]);

        return $apps;
    }

    /**
     * Get list of modules from an app
     * 
     * @param array $app Array containing the name and path of the app
     * @throws Exception if the modules file is not found
    */
    public function get_module_list($app) : array  // Array of module names from the app
    {
        if(!$this->files->exists($app_name = $app['path'].DS.'modules.txt')) {
            throw new Exception("File not found at app: " . $app['name']);
        }

        return $this->get_file_items($app_name);
    }

    /**
     * Get items from a file by path.
     *
     * @param string $path The path to the file.
     * @param bool $raiseNotFound Whether to raise an exception if the file is not found.
     * @param bool $ignoreEmptyLines Whether to ignore empty lines in the file.
     * @throws Exception if the file is not found and $raiseNotFound is true.
    */
    public function get_file_items($path, $raiseNotFound = false, $ignoreEmptyLines = true) : array
    {
        $content = $this->files->get($path);
        if (!empty($content)) {
            $content = trim($content);
            $items = explode("\n", $content);
            if ($ignoreEmptyLines) {
                $items = array_filter($items, function ($item) {
                    return !empty(trim($item)) && !str_starts_with($item, "#");
                });
            }
            return $items;
        } elseif ($raiseNotFound) {
            throw new Exception("File not found at path: " . $path);
        }
        return [];
    }
    
    /**
     * Return a `Erp\Foundation\BaseDocument` object of the given type and name .
     *
     * @param  $arguments
     */
    public function get_doc(...$arguments) : \Erp\Foundation\BaseDocument
    {
        if(!$arguments || empty($arguments)){
            throw new Exception("First non keyword argument must be a string or array");
        }
        $arg = $arguments[0];
        if($arg instanceof BaseDocument){
            return $arg;
        }elseif(is_string($arg)){
            $doctype = $arg;
        }elseif(is_array($arg) && array_key_exists("doctype", $arg)){
            $doctype = $arg["doctype"];
        }else{
            throw new Exception('"doctype" is a required key');
        }

        $controller = $this->get_controller($doctype);
        if ($controller){
            return $controller(...$arguments);
        }
    }

    /**
     * Returns a new document of the given DocType with defaults set.
     *
     * @param  $args
     */
    public function new_doc($doctype, $parent_doc = null, $parentfield = null, $as_dict = false) : \Erp\Foundation\BaseDocument
    {
        if (!array_key_exists($doctype, app('erp')->new_doc_templates)) {
            $this->new_doc_templates[$doctype] = $this->make_new_doc($doctype);
        }
    
        $doc = clone app('erp')->new_doc_templates[$doctype];
    
        // set_dynamic_default_values($doc, $parent_doc, $parentfield);
    
        if ($as_dict) {
            return $doc;
        } else {
            return $this->get_doc($doc);
        }
    }

    protected function make_new_doc($doctype) : \Erp\Foundation\BaseDocument
    {
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
    
        if (!$doc->meta->is_single) {
            $doc["__unsaved"] = 1;
        }
    
        return $doc;
    }

    public function get_singles_dict($doctype) : array
    {
        $result = Single::select(['fieldname', 'value'])->where('doctype', $doctype)->get()->toArray();

        return array_reduce($result, function ($carry, $item) {
            $carry[$item['fieldname']] = $item['value'];
            return $carry;
        }, []);
    }

    /**
     * Retrieve the metadata for a given document type.
     *
     * @param string $doctype The name of the document type to retrieve metadata for.
     */
    public function get_meta($doctype) : \Erp\Foundation\Meta
    {
        return new Meta($doctype);
    }
}
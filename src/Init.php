<?php

namespace LaravelErp;

use LaravelErp\Contracts\Cache;
use LaravelErp\Models\Single;
use LaravelErp\Modules\BaseDocument;
use LaravelErp\Modules\Meta;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Exception;

class Init
{
    use Traits\Document;

    /**
     * The version of the class.
     *
     * @var string
     */
    public $__version__ = "1.0.0";

    /**
     * Class constructor.
     *
     * @param Filesystem|null $files Optional file system instance
     * @param array $new_doc_templates Array of new document templates
     * @param array $module_app Array of module applications
     * @param array $app_modules Array of application modules
     */
    public function __construct(
        public ?Filesystem $files = new Filesystem,
        public array $all_apps,
        public array $app_modules,
        public array $module_app,
        public array $erp_config,
        public array $new_doc_templates = [])
    {   
        $this->controllers = (object) [];
        $this->flags = new Cache('flags');

        $this->local = (object) [
            'valid_columns' => []
        ];
    }

    /**
     * Get a list of all the installed applications
     * 
    */
    public function app_file() : string
    {
        return \App::joinPaths($this->erp_config['path'], $this->erp_config['app']);
    }

    /**
     * Get a list of all the installed applications
     * 
    */
    public function get_all_apps() : Collection
    {
        $filePath = $this->app_file();

        if (empty($this->all_apps)){
            // get erp path dan filename
            if($this->files->exists($filePath)) {
                foreach (explode("\r\n", $this->files->get($filePath)) as $app) {
                    $is_app = \App::joinPaths($this->erp_config['path'], $app.DS.'setup.json');
                    if($this->files->exists($is_app)){
                        $setup = $this->files->get($is_app);
                        array_push($this->all_apps,
                            [
                                'name' => $app,
                                'path' => $setup->path,
                                'namespace' => $setup->namespace 
                            ]
                        );
                    }
                }
            }

            array_unshift($this->all_apps, [
                'name' => 'laravel-erp',
                'path' => __DIR__,
                'namespace' => __NAMESPACE__
            ]);
        }

        return new Collection($this->all_apps);
    }

    /**
     * Get a the installed application
     * 
    */
    public function get_app($name) : array
    {
        $apps = new Collection($this->all_apps);
        return $apps->firstWhere('name', $name) ?? [];
    }
    
    /**
     * Get list of modules from an app
     * 
     * @param array $app Array containing the name and path of the app
     * @throws Exception if the modules file is not found
    */
    public function get_module_list($app) : array
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
     * Return a `LaravelErp\Modules\BaseDocument` object of the given type and name .
     *
     * @param  $arguments
     */
    public function get_doc(...$arguments) : BaseDocument
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
    public function new_doc($doctype, $parent_doc = null, $parentfield = null, $as_dict = false) : BaseDocument
    {
        if (!array_key_exists($doctype, $this->new_doc_templates)) {
            $this->new_doc_templates[$doctype] = $this->make_new_doc($doctype);
        }
    
        $doc = clone $this->new_doc_templates[$doctype];
    
        // set_dynamic_default_values($doc, $parent_doc, $parentfield);
    
        if ($as_dict) {
            return $doc;
        } else {
            return $this->get_doc($doc);
        }
    }

    /**
     * Creates a new document of the specified doctype.
     * 
     * @param string $doctype The doctype of the document to create.
     * @return BaseDocument The newly created document.
    */
    protected function make_new_doc($doctype) : BaseDocument
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

    /**
     * Get the dictionary of single doctype records for the given doctype.
     * 
     * @param string $doctype The name of the doctype.
     * @return array An array of key-value pairs representing the single doctype records.
    */
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
    public function get_meta($doctype) : Meta
    {
        return new Meta($doctype);
    }

    /**
     * Get the app name associated with the given module.
     * 
     * @param string $module The name of the module.
     * @return string|null The name of the app associated with the given module or null if not found.
    */
    function get_module_app($module)
    {
        return $this->get_app($this->module_app[scrub($module, FALSE)])['namespace'];
    }
}
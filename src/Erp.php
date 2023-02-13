<?php

namespace Erp;

// use Erp\Models\DB as Doctype;
use Erp\Models\Single;
use Erp\Foundation\Meta;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Exception;

class Erp
{
    use Traits\Utils;

    public $app_modules;

    public $module_app;

    public $controllers;

    public $new_doc_templates = [];
    
    /**
     * Create a new migration install command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files = null)
    {   
        $this->files = $files ?: new Filesystem;
    }

    public function get_singles_dict($doctype)
    {
        $result = Single::select(['fieldname', 'value'])->where('doctype', $doctype)->get()->toArray();

        return array_reduce($result, function ($carry, $item) {
            $carry[$item['fieldname']] = $item['value'];
            return $carry;
        }, []);
    }

    // def get_singles_dict(self, doctype, debug=False):
	// 	"""Get Single DocType as dict.

	// 	:param doctype: DocType of the single object whose value is requested

	// 	Example:

	// 	        # Get coulmn and value of the single doctype Accounts Settings
	// 	        account_settings = frappe.db.get_singles_dict("Accounts Settings")
	// 	"""
	// 	result = self.sql(
	// 		"""
	// 		SELECT field, value
	// 		FROM   `tabSingles`
	// 		WHERE  doctype = %s
	// 	""",
	// 		doctype,
	// 	)

	// 	dict_ = frappe._dict(result)

	// 	return dict_
    public function generate_hash($txt = null, $length = null) {
        $digest = Hash::make($txt . time() . Str::random(8));
        if ($length) {
            return substr($digest, 0, $length);
        }
        return $digest;
    }

    public function get_all_apps()
    {
        $apps = [];
        
        [$path, $file] = $this->appFile('app');

        if($this->files->exists($path.$file)) {
            // throw new LogicException("File Not Found");
        }

        array_push($apps, ['name'=> 'Erp', 'path' => __DIR__]);

        return $apps;
    }

    public function get_module_list($app)
    {
        if(!$this->files->exists($app_name = $app['path'].DS.'modules.txt')) {
            throw new Exception("File not found at path: " . $app['name']);
        }

        return $this->get_file_items($app_name);
    }

    public function get_file_items($path, $raiseNotFound = false, $ignoreEmptyLines = true)
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

    
    public function get_meta($doctype)
    {
        return new Meta($doctype);
    }

    // /**
    //  * Set a given configuration value.
    //  *
    //  * @param  array|string  $key
    //  * @param  mixed  $value
    //  * @return void
    //  */
    // public function db(string $docType)
    // {
    //     return Doctype::doc($docType);
    // }

    // public function get_doc($doctype)
    // {
    //     $controller = get_controller($doctype);
	//     if ($controller){
	// 	    return controller();
    //     }
    //     // return new BaseDocument($doctype)
    // }
}
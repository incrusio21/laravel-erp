<?php

namespace Erp\Foundation;

use Illuminate\Support\Facades\File;
use Exception;

function load_doctype_from_file($doctype) {
    $fname = scrub($doctype, FALSE);
    $file = __DIR__.DS.'..'.DS.'Core'.DS.'Controller'.DS.$fname.DS.'form.json';
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
            return new BaseDocument($d);
        }, $txt['fields']);
        // $txt['permissions'] = $permissions;
        
        return $txt;
    } else {
        throw new Exception("{$doctype} not found");
    }
}

class Meta extends Document
{
    use \Erp\Traits\Models;

    protected $_metaclass = True;
	
	protected $special_doctypes = [
        "App",
        "Apps",
		"DocField",
		"DocPerm",
		"DocType",
		"Module Def",
		"DocType Action",
		"DocType Link",
    ];

    /**
     * Create a new migration install command instance.
     * @return void
     */
    public function __construct($doctype)
    {   
        $this->default_fields = array_slice($this->default_fields, 1);

        $this->_fields = [];
        
        if($doctype instanceof Document){
            parent::__construct($doctype);
        }else{
            parent::__construct("DocType", $doctype);
        }

        $this->process();
    }

    // Load document and children from database and create properties from fields
    protected function process()
    {
        $this->get_valid_columns();
        
        //  don't process for special doctypes
		// prevent's circular dependency
        if (in_array($this->name, $this->special_doctypes)) return;

        // self.add_custom_fields()
		// self.apply_property_setters()
		// self.sort_fields()
		// self.set_custom_permissions()
		// self.add_custom_links_and_actions()
    }

    // Load document and children from database and create properties from fields
    protected function load_from_db()
    {
        try {
            parent::load_from_db();
        }catch (\Erp\Exceptions\DoesNotExistError $e) {
            if ($this->doctype == "DocType" && in_array($this->name, $this->special_doctypes)) {
                $this->update(load_doctype_from_file($this->name));
            } else {
                throw $e;
            }
        }
    }

    protected function get_table_fields($field = null)
    {
        if (!property_exists($this, "_table_fields")) {
            if ($this->name != "DocType") {
                $this->_table_fields = $this->get("fields", ["fieldtype" => ["in", $this->table_fields]]);
            }
        }

        return $this->_table_fields;
    }


    protected function get_field($fieldname = null)
    {
        if (isset($this->_fields)) {
            foreach($this->get("fields") as $f){
                $this->_fields[$f->fieldname] = $f;
            }
        }

        return $this->_fields[$fieldname];
    }

    protected function get_valid_columns()
    {
        if (!property_exists($this, "_valid_columns")) {
            $this->_valid_columns = array_merge($this->default_fields, 
                array_column(
                    array_filter(
                        $this->get('fields'), function ($df) {
                            return in_array($df->fieldtype, $this->data_fieldtypes);
                        }
                    ), 'fieldname'
                )
            );
        }

        // if not hasattr(self, "_valid_columns"):
		// 	table_exists = frappe.db.table_exists(self.name)
		// 	if self.name in self.special_doctypes and table_exists:
		// 		self._valid_columns = get_table_columns(self.name)
		// 	else:
		// 		self._valid_columns = self.default_fields + [
		// 			df.fieldname for df in self.get("fields") if df.fieldtype in data_fieldtypes
		// 		]
        
		return $this->_valid_columns;
    }
}
<?php

namespace Erp\Foundation;

use Illuminate\Support\Facades\File;
use Exception;

class Meta extends Document
{
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
        $this->default_fields = array_slice(config('doctype.default_fields'), 1);

        $this->_fields = [];
        
        if($doctype instanceof Document){
            $this->__invoke($doctype);
        }else{
            $this->__invoke("DocType", $doctype);
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
                $this->update($this->load_doctype_from_file($this->name));
            } else {
                throw $e;
            }
        }
    }

    protected function get_table_fields($field = null)
    {
        if (!property_exists($this, "_table_fields")) {
            if ($this->name != "DocType") {
                $this->_table_fields = $this->get("fields", ["fieldtype" => ["in", config('doctype.table_fields')]]);
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
        return $this->_fields[$fieldname] ?? null;
    }
    
    protected function get_valid_columns()
    {
        if (!property_exists($this, "_valid_columns")) {
            $this->_valid_columns = array_merge($this->default_fields, 
                array_column(
                    array_filter(
                        $this->get('fields'), function ($df) {
                            return in_array($df->fieldtype, config('doctype.data_fieldtypes'));
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
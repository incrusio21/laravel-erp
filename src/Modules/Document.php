<?php

namespace LaravelErp\Modules;

use LaravelErp\Models\DB;
use LaravelErp\Models\Naming;
use LaravelErp\Models\Single;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Exception;

class Document extends BaseDocument
{    
    /**
     * Initialize the object with the given arguments.
     *
     * @param mixed ...$arguments - The arguments to initialize the object.
     *     If the first argument is a string, it is assumed to be the doctype name.
     *     If a second argument is provided and it is an array, it is assumed to be the fields of the doctype.
     *     Otherwise, the second argument is assumed to be the name of the doctype and the fields are loaded from the database.
     *     If the first argument is an array, it is passed to the parent constructor.
     * @throws Exception if the arguments are not valid
     * @return void
     */
    public function __invoke(...$arguments)
    {
        $this->doctype = $this->name = NULL;
		$this->_default_new_docs = (object) [];
		$this->flags = (object) [];

        if(!$arguments || empty($arguments)){
            throw new Exception("Illegal arguments");
        }

        if(isset($arguments[0]) && is_string($arguments[0])){
            // first argument is doctype
            if (count($arguments) == 1){
                // single
                $this->doctype = $this->name = $arguments[0];
            } else {
                $this->doctype = $arguments[0];
                if(is_array($arguments[1])){
                    // second argument is fields
                } else {
                    // second argument is name
                    $this->name = $arguments[1];
                }
            }
            $this->load_from_db();
        }

        if(isset($arguments[0]) && is_array($arguments[0])){
            parent::__invoke($arguments[0]);
        }

        return $this;
    }

    // Load document and children from database and create properties from fields
    protected function load_from_db()
    {
        if (!property_exists($this, "_metaclass") && $this->meta->is_single) {
            $single_doc =  app('laravel-erp')->get_singles_dict($this->doctype);
            if(!$single_doc){
                $single_doc = (array) app('laravel-erp')->new_doc($this->doctype);
                $single_doc['name'] = $this->doctype;
                unset($single_doc['__islocal']);
            }

            parent::__invoke($single_doc);
        }else{
            if (Schema::hasTable($this->get_table_name($this->doctype))) {
                $d = DB::doc($this->doctype)->find($this->name);
            }
            if(!isset($d)){
                throw new \LaravelErp\Exceptions\DoesNotExistError("{$this->doctype} {$this->name} not found");
            }
            parent::__invoke($d);
        }

        $table_fields = $this->meta->get_table_fields();
        
        foreach($table_fields as $df){
            $children = DB::doc($df->options)->where(['parent_name' => $this->name, 'parent_type' => $this->doctype, 'parent_field' => $df->fieldname])->get()->toArray();
            $this->set($df->fieldname, $children ?? []);
        }

        // sometimes __setup__ can depend on child values, hence calling again at the end
		if (method_exists($this, "__setup__")) {
            $this->__setup__();
        }
    }

    /**
     * Inserts the current document instance into the database.
     *
     * @param string|null $set_name The new name to set for the document instance (if any).
     * @param bool $set_child_names Whether to set new names for child documents (default: true).
     *
     * @return void
     *
     * @throws DuplicateEntryException If a duplicate entry is detected during insertion.
     */
    protected function insert($set_name=Null, $set_child_names=True)
    {
        // Set the new name for the document instance
        $this->set_new_name(set_name:$set_name, set_child_names:$set_child_names);

        // Insert the parent document into the database
        if (property_exists($this->meta, 'is_single') && $this->meta->is_single) {
            $this->update_single($this->get_valid_dict());
        } else {
            try {
                $this->db_insert();
            } catch (DuplicateEntryException $e) {
                if (!$ignoreIfDuplicate) {
                    throw $e;
                }
            }
        }

		// Insert child documents into the database
		foreach ($this->get_all_children() as $d) {
            if(!$d->parent_name){
                $d->parent_name = $this->name;
            }
            $d->db_insert();
        }
    }

    /**
     * Saves the current document instance to the database.
     *
     * If the document instance is new (i.e., has not been saved to the database before),
     * the `insert` method will be called to insert the document into the database.
     *
     * If the document instance already exists in the database, the `db_update` method will
     * be called to update the document.
     *
     * @return void
     *
     * @throws DuplicateEntryException If a duplicate entry is detected during insertion.
     */
    public function save()
    {
        // If the document instance is new, insert it into the database
        if ($this->get("__islocal") || !$this->get("name")) {
            return $this->insert();
        }

        // Update the existing document instance in the database
        if (property_exists($this->meta, 'is_single') && $this->meta->is_single) {
            $this->update_single($this->get_valid_dict());
        } else {
            $this->db_update();
        }

        // Update any child documents associated with the current document instance
		$this->update_children();
    }


    protected function update_children()
    {
        foreach ($this->meta->get_table_fields() as $df) {
            $this->update_child_table($df->fieldname, $df);
        }
    }

    protected function update_child_table($fieldname, $df = NULL)
    {
        $rows = [];

        if(!$df){
            $df = $this->meta->get_field($fieldname);
        }

        foreach ($this->get($df->fieldname) as $key => $d) {
            $d->idx = ++$key;

            $d->db_update();
            $rows[] = $d->name;
        }

        $deletRow = DB::doc($df->options)->where(['parent_name' => $this->name, 'parent_type' => $this->doctype, 'parent_field' => $df->fieldname]);
        if($rows){
            // delete rows that do not match the ones in the document
            $deletRow->whereNotIn('name', $rows)->delete();
        }else{
            # no rows found, delete all rows
            $deletRow->delete();
        }
    }

    /**
     * Set a new name for the document based on its doctype and its configuration.
     * 
     * @param bool $force Set to true to force the setting of a new name even if it has already been set.
     * @param string|null $set_name A specific name to be set for the document.
     * @param bool $set_child_names Set to true to also set new names for child documents.
     * @return void
    */
    protected function set_new_name($force=FALSE, $set_name = NULL, $set_child_names=TRUE)
    {
        if (($this->flags->name_set ?? FALSE) && !$force){
            return;
        }
    
        # If autoname has set as Prompt (name)
        if($this->get("__newname")){
            $this->name = validate_name($this->doctype, $this->get("__newname"));
            $this->flags->name_set = TRUE;
            return;
        }

        if($set_name){
            $this->name = $this->validate_name($this->doctype, $set_name);
        }else{
            Naming::set_new_name($this);
        }

	    $this->flags->name_set = TRUE;
    }

    /**
     * Update the single document with the given dictionary values.
     * 
     * @param array $d The dictionary containing the values to update in the single document.
     * @return void
    */
    protected function update_single($d)
    {
        foreach ($d as $field => $value) {
            if ($field != "doctype") {
                Single::updateOrCreate(
                    ['doctype' => $this->doctype, 'fieldname' => $field],
                    ['value' => $value]
                );
            }
        }

        // if self.doctype in frappe.db.value_cache:
		// 	del frappe.db.value_cache[self.doctype]
    }
}
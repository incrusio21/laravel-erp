<?php

namespace Erp\Foundation;

use Erp\Controllers\Document as ControllerDoc;
use Erp\Foundation\BaseDocument;
use Erp\Models\DB;
use Erp\Models\Naming;
use Erp\Models\Single;

class Document extends BaseDocument
{
    /**
     * Set the template from the table config file if it exists
     *
     * @param   array   $config (default: array())
     * @return  void
     */
    public function __construct($kwargs, ...$args)
    {
        if(is_string($kwargs)){
            $this->doctype = $kwargs;
            if(count($args) == 0){
				# single
				$this->name = $kwargs;
            }else{
                $this->name = $args[0];
            }

            $this->load_from_db();
            return;
        }

        if(is_array($kwargs)){
            parent::__construct($kwargs);
        }else{
            throw new \Exception("Illegal arguments");
        }
    }

    // Load document and children from database and create properties from fields
    protected function load_from_db()
    {
        if (!property_exists($this, "_metaclass") && $this->meta->is_single) {
            $single_doc = erp('get_singles_dict', $this->doctype);
            if(!$single_doc){
                $single_doc = (array) ControllerDoc::new_doc($this->doctype);
                $single_doc['name'] = $this->doctype;
                unset($single_doc['__islocal']);
            }

            parent::__construct($single_doc);
        }else{
            $d = doc_value($this->doctype)->find($this->name);
            if(!$d){
                throw new \Erp\Exceptions\DoesNotExistError("{$this->doctype} {$this->name} not found");
            }
            parent::__construct($d);
        }

        $table_fields = $this->meta->get_table_fields();
        
        foreach($table_fields as $df){
            $children = DB::doc($df->options)->where(['parent_name' => $this->name, 'parent_type' => $this->doctype, 'parent_field' => $df->fieldname])->get()->toArray();
            $this->set($df->fieldname, $children ?? []);
        }

        // print_r($table_fields);

        // # sometimes __setup__ can depend on child values, hence calling again at the end
		// if hasattr(self, "__setup__"):
		// 	self.__setup__()
    }

    protected function insert($set_name=Null, $set_child_names=True)
    {

        $this->set_new_name(set_name:$set_name, set_child_names:$set_child_names);

        // parent
        if (property_exists($this->meta, 'is_single') && $this->meta->is_single) {
            $this->update_single($this->get_valid_dict());
        } else {
            // try {
            $this->db_insert();
            // } catch (DuplicateEntryException $e) {
            //     if (!$ignoreIfDuplicate) {
            //         throw $e;
            //     }
            // }
        }
		// if getattr(self.meta, "issingle", 0):
		// 	self.update_single(self.get_valid_dict())
		// else:
		// 	try:
		// 		self.db_insert()
		// 	except frappe.DuplicateEntryError as e:
		// 		if not ignore_if_duplicate:
		// 			raise e
		// children
        // print_r($this);

		foreach ($this->get_all_children() as $d) {
            $d->db_insert();
        }
    }

    public function save()
    {
        if ($this->get("__islocal") || !$this->get("name")) {
            return $this->insert();
        }

        if (property_exists($this->meta, 'is_single') && $this->meta->is_single) {
            $this->update_single($this->get_valid_dict());
        } else {
            $this->db_update();
        }

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

        foreach ($this->get($df->fieldname) as $d) {
            $d->db_update();
            $rows[] = $d->name;
        }

        if($rows){
            print_r($rows);
            // delete rows that do not match the ones in the document
            DB::doc($df->options)->where(['parent_name' => $this->name, 'parent_type' => $this->doctype, 'parent_field' => $df->fieldname])->whereNotIn('name', $rows)->delete();
        }else{
            # no rows found, delete all rows
        }
    }

	// Calls `frappe.naming.set_new_name` for parent and child docs.
    protected function set_new_name($force=FALSE, $set_name = NULL, $set_child_names=TRUE)
    {
        // 	if self.flags.name_set and not force:
    	// 		return  
    
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

	    // 	self.flags.name_set = True
        
    }
    // Updates values for Single type Document in `tabSingles`.
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
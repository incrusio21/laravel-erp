<?php

namespace Erp\Foundation;

use Erp\Controllers\Document;
use Erp\Models\DB;
use Erp\Models\Naming;

class BaseDocument
{
    protected $ignore_in_setter = [ "doctype", "_meta", "meta", "_table_fields", "_valid_columns" ];

    /**
     * Set the template from the table config file if it exists
     *
     * @param   array   $config (default: array())
     * @return  void
     */
    public function __construct($d)
    {
        if (array_key_exists("doctype", $d)) {
            $this->doctype = $d["doctype"];
        }

        $this->update($d);
    }
    
    protected function update(array $d)
    {
        if (array_key_exists("name", $d)) {
            $this->name = $d["name"];
        }
        
        foreach ($d as $key => $value) {
            if (strpos($key, "\0") === 0) {
                continue;
            }

            $this->set($key, $value);
        }
    
        return $this;
    }

    protected function get(?string $key = null, $filters = null, ?int $limit = null, $default = null)
    {
        if ($key) {
            if (is_array($key) || is_object($key)) {
                return filter($this->get_all_children(), $key, $limit);
            }
            if ($filters) {
                if (is_array($filters)) {
                    $value = filter($this->$key ?? [], $filters, $limit);
                } else {
                    $default = $filters;
                    $filters = null;
                    $value = $this->$key ?? $default;
                }
            }else {
                $value = $this->$key ?? $default;
            }
            
            if ($value === null && in_array($key, $this->meta->get_table_fields('fieldname'))) {
                $value = [];
                $this->set($key, $value);
            }
            // if ($limit && is_array($value) && count($value) > $limit) {
            //     $value = array_slice($value, 0, $limit);
            // }

            return $value;
        }

        return get_object_vars($this);
    }

    public function set(string $key, $value, bool $asValue = false)
    {
        if (in_array($key, $this->ignore_in_setter)) {
            return;
        }

        if (is_array($value) && ! $asValue) {
            $this->{$key} = [];
            $this->extend($key, $value);
        } else {
            $this->{$key} = $value;
        }
    }

    public function append(string $key, $value = Null)
    {
        if ($value == Null){
            $value = [];
        }

        if (is_array($value) || $value instanceof BaseDocument) {
            if (!isset($this->$key)) {
                $this->$key = [];
            }

            $value = $this->_init_child($value, $key);
            
            $value->parent_doc = $this;
            
            $this->$key[] = $value;

            return $value;
        }
        
        // metaclasses may have arbitrary lists
        // which we can ignore
        // || $this instanceof FormMeta || $this instanceof DocField
        if (property_exists($this, "_metaclass") || $this instanceof Meta ) {
            return $value;
        }

        throw new \Exception(
            'Document for field "'.$key.'" attached to child table of "'.$this->name.'" must be a Array or BaseDocument, not '.gettype($value).' ('.$value.')'
        );
    }

    protected function extend(string $key, $value)
    {
        if (is_array($value)) {
            foreach ($value as $v) {
                $this->append($key, $v);
            }
        } else {
            throw new \InvalidArgumentException("Expected value to be an array, but got " . gettype($value));
        }
    }


    protected function _init_child($value, string $key, int $idx = Null)
    {
        if(!property_exists($this, 'doctype')){
            return $value;
        }

        if (!$value instanceof BaseDocument) {
            $value['doctype'] = $this->get_table_field_doctype($key);
            if (!$value['doctype']) {
                throw new AttributeError($key);
            }

            $contoller = Document::get_controller($value["doctype"]);
            $value = new $contoller ($value);
        }

        $value->parent_name = $this->name;
		$value->parent_type = $this->doctype;
		$value->parent_field = $key;
        

        if(!property_exists($value, 'docstatus')) {
            $value->docstatus = 0;
        }

        if(!property_exists($value, 'idx')) {
            $value->idx = $idx ?? (count($this->get($key) ?? []) + 1);
        }

        if(!property_exists($value, 'name')) {
            $value->__islocal = 1;
        }

		// if not getattr(value, "idx", None):
		// 	value.idx = len(self.get(key) or []) + 1

        // Implement initialization logic for child
        return $value;
    }

    protected function get_valid_dict($sanitize=True, $convert_dates_to_str=False, $ignore_nulls=False)
    {
        $d = [];
        
        foreach($this->meta->get_valid_columns() as $fieldname){
            $d[$fieldname] = $this->get($fieldname);
        }
        
        return $d;
    }


    protected function get_valid_columns()
    {
        return [];
    }

    protected function get_table_field_doctype($fieldname)
    {
        try {
            return $this->meta->get_field($fieldname)->options;
        }catch (\Erp\Exceptions\DoesNotExistError $e) {
            if ($this->doctype == "DocType") {
                throw $e;
            } else {
                throw $e;
            }
        }
    }

    protected function get_all_children(?string $parentType = null)
    {
        $children = [];

        foreach ($this->meta->get_table_fields() as $df) {
            if ($parentType && $df->options !== $parentType) {
                continue;
            }
            

            if (is_array($value = $this->get($key = $df->fieldname))) {
                foreach(array_filter($value, function($d){ return !$d instanceof BaseDocument; }) as $idx => $data){
                    $value[$idx] = $this->_init_child($data, $key, $idx+1);
                }

                $children = array_merge($children, $value);
            }
        }

        return $children;
    }

    // INSERT the document (with valid columns) in the database.
    protected function db_insert()
    {
        if(!$this->name){
            // name will be set by document class in most cases
			Naming::set_new_name($this);
        }
		
        if ($this->creation){
			$this->created_by = $this->modified_by = '';
        }

        $colums = $this->get_valid_dict() ?? [];

        try {
            $db_doc = DB::doc($this->doctype)->create($colums);
        }catch (\Exception $e) {
            throw $e;
        }
    }

    // Update the document (with valid columns) in the database.
    protected function db_update()
    {
        if ($this->get("__islocal") || !$this->name){
			return $this->db_insert();
        }

        $colums = $this->get_valid_dict() ?? [];
        # don't update name, as case might've been changed
        $name = $colums["name"];
        unset($colums["name"]);

        try {
            $db_doc = DB::doc($this->doctype)->where('name', $name)->update($colums);
        }catch (\Exception $e) {
            throw $e;
        }
        
    }

    public function __get($property)
    {
        if ($property === 'meta') {
            if (!property_exists($this, '_meta')) {
                $this->_meta = erp('get_meta', $this->doctype);
            }

            return $this->_meta;
        }

        return null;
    }
}

function filter($data, $filters, $limit = null) 
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
            if (!compare(data_get($d, $f), $fval[0], $fval[1])) {
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

function compare($value, $operator, $reference)
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
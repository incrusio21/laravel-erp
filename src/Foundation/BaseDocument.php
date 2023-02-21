<?php

namespace Erp\Foundation;

use Erp\Controllers\Document;
use Erp\Models\DB;
use Erp\Models\Naming;

class BaseDocument
{
    use \Erp\Traits\Utils,
        \Erp\Traits\Document;

    protected $ignore_in_setter = [ "doctype", "_meta", "meta", "_table_fields", "_valid_columns" ];

    /**
     * Construct a new instance of the object and set its properties.
     *
     * @param array $d An array of properties to set on the object.
     * @return void
     */
    public function __invoke($d)
    {
        if (array_key_exists("doctype", $d)) {
            $this->doctype = $d["doctype"];
        }

        $this->update($d);

        return $this;
    }

    /**
     * Retrieve the value of the specified key or all object properties if no key is provided.
     *
     * @param string|null $key The key to retrieve the value for, or null to retrieve all object properties.
     * @param mixed|null $filters Optional filters to apply when retrieving the value, or null to apply no filters.
     * @param int|null $limit Optional limit to apply to the number of values retrieved, or null to apply no limit.
     * @param mixed $default The default value to return if the specified key is not set.
     * @return mixed The value of the specified key or all object properties, or the default value if the key is not set.
     */
    protected function get(?string $key = null, $filters = null, ?int $limit = null, $default = null)
    {
        if ($key) {
            if (is_array($key) || is_object($key)) {
                return $this->filter($this->get_all_children(), $key, $limit);
            }
            if ($filters) {
                if (is_array($filters)) {
                    $value = $this->filter($this->$key ?? [], $filters, $limit);
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

    /**
     * Update the model instance with the given attributes.
     *
     * @param array $d The attributes to update.
     * @return $this The updated model instance.
     */
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

    /**
     * Set the given value for the given key.
     *
     * @param string $key The key to set the value for.
     * @param mixed $value The value to set.
     * @param bool $asValue Whether the value should be set directly or merged with an existing array.
     * @return void
     */
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

    /**
     * Append a value to the specified key, creating the key if it does not exist.
     * If $value is null, an empty array is created for that key.
     * If $value is an array or an instance of BaseDocument, a new child document is created.
     * If this is a meta class, or an instance of FormMeta or DocField, the value is returned without appending it.
     * 
     * @param string $key The key to append the value to
     * @param mixed $value (optional) The value to append to the key, defaults to null
     * @throws Exception If $value is not an array or an instance of BaseDocument and the document for the field is attached to a child table
     * @return BaseDocument|null The created child document, or null if $value is not an array or an instance of BaseDocument and this is a meta class, FormMeta, or DocField.
    */
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
            
            // $value->parent_doc = $this;
            
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

    /**
     * Extends the given key with the given array of values
     * 
     * @param string $key The key to extend
     * @param array $value The values to append to the key
     * @throws InvalidArgumentException if the value is not an array
     * @return void
    */
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

    /**
     * Initialize a child document and attach necessary properties.
     *
     * @param mixed $value
     * @param string $key
     * @param int|null $idx (default: null)
     * @return mixed
     * @throws AttributeError if the doctype for the given key cannot be determined
    */
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
            $contoller = $this->get_controller($value["doctype"]);
            $value = $contoller($value);
        }
        
        $value->parent_name = property_exists($this, 'name') ? $this->name : '';
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

        // Implement initialization logic for child
        return $value;
    }

    /**
     * Get a valid dictionary representation of the object, optionally sanitizing values and converting dates to string.
     * 
     * @param bool $sanitize Set to true to sanitize values, false otherwise.
     * @param bool $convert_dates_to_str Set to true to convert date objects to strings, false otherwise.
     * @param bool $ignore_nulls Set to true to ignore null values, false otherwise.
     * @return array A valid dictionary representation of the object.
     * @throws Exception if the value for a field is a list when it should not be.
    */
    protected function get_valid_dict($sanitize=True, $convert_dates_to_str=False, $ignore_nulls=False)
    {
        $d = [];
        
        $table_fields = config('doctype.table_fields');

        foreach($this->meta->get_valid_columns() as $fieldname){
            $d[$fieldname] = $this->get($fieldname);

            $df = $this->meta->get_field($fieldname);

            if($df){
                if ($df->fieldtype == 'Check') {
                    $d[$fieldname] = (int) ($d[$fieldname] !== false);
                }elseif ($df->fieldtype === 'Int' && !is_int($d[$fieldname])) {
                    $d[$fieldname] = (int) $d[$fieldname];
                }elseif (in_array($df->fieldtype, ["Currency", "Float", "Percent"]) && !is_float($d[$fieldname])) {
                    $d[$fieldname] = floatval($d[$fieldname]);
                }elseif (in_array($df->fieldtype, ["Datetime", "Date", "Time"]) && empty($d[$fieldname])) {
                    $d[$fieldname] = null;
                }elseif (property_exists($df, 'unique') && $df->unique && trim(strval($d[$fieldname])) === '') {
                    $d[$fieldname] = null;
                }

                if (is_array($d[$fieldname]) && !in_array($df['fieldtype'], $table_fields)) {
                    throw new Exception("Value for {$df['label']} cannot be a list");
                }
            }
            if ($convert_dates_to_str && (
                $d[$fieldname] instanceof DateTime 
                || $d[$fieldname] instanceof Date 
                || $d[$fieldname] instanceof Time 
                || $d[$fieldname] instanceof DateInterval
            )) {
                $d[$fieldname] = strval($d[$fieldname]);
            }
            
            if(!$d[$fieldname] && $ignore_nulls){
				unset($d[$fieldname]);
            }
        }
        
        return $d;
    }

    /**
     * Returns the list of valid columns for the current document type. Caches the result
     * to minimize repetitive calls to get_valid_columns on the meta instance.
     * 
     * @return array An array containing the list of valid columns for the current document type
    */
    protected function get_valid_columns()
    {
        if(!in_array($this->doctype, app('erp')->local->valid_columns)){
            $valid = $this->meta->get_valid_columns();

            app('erp')->local->valid_columns[$this->doctype] = $valid;
        }

        return app('erp')->local->valid_columns[$this->doctype];
    }

    /**
     * Retrieve the doctype of a table field.
     * 
     * @param string $fieldname The name of the table field.
     * @throws \Erp\Exceptions\DoesNotExistError if the table field doesn't exist.
     * @return string The name of the doctype of the table field.
    */
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
    
    /**
     * Get all child documents of the current document object.
     * 
     * @param string|null $parentType If provided, only children of this doctype will be returned
     * @return array An array of child documents, empty if none are found
    */
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

    /**
     * Insert the current document object into the database.
     * If the name property is not set, it will be set using Naming::set_new_name().
     * If the creation property is truthy, created_by and modified_by properties will be set to an empty string.
     * The document's valid dictionary will be used to create the database record via the DB::doc() method.
     * 
     * @throws Exception if an error occurs while creating the database record
     * @return void
    */
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

    /**
     * Update a document in the database.
     * If the document doesn't exist in the database, it will be inserted instead.
     * 
     * @throws Exception If the update operation fails for any reason.
     * @return void
    */
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
                $this->_meta = app('erp')->get_meta($this->doctype);
            }

            return $this->_meta;
        }

        return $this->$property ?? NULL;
    }
}
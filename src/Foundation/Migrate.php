<?php

namespace Erp\Foundation;

use Erp\Models\Single;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class Migrate
{
    /**
     * Constructs a new instance of the class and initializes the "doctype" property.
     * @return void
    */
    public function __construct() {
        $this->doctype = config('doctype');
    }

    /**
     * Creates or updates a database table for a given document type.
     * 
     * @param object $cont The document type object containing the table name and fields.
     * @throws InvalidArgumentException if the document type object does not have either 'name' or 'fields' properties, or the document type is excluded.
     * @return void
    */
    public function create_or_update_table($cont)
    {
        if(!(property_exists($cont, 'name') && property_exists($cont, 'fields'))){
            throw new InvalidArgumentException("Object tidak memiliki salah satu dari key [name, field]");
        }

        $parent = property_exists($cont, 'parent_name') ? $cont->parent_name : null;
        if(in_array($cont->name, config('erp.except_doc', []))){
            throw new InvalidArgumentException("Doctype with name [".($cont->name)."] cannot to be used.");
        }

        $name = $cont->name;

        app('flags')->set('document', [$name => $cont]);
        
        if(property_exists($cont, 'is_single') && $cont->is_single){
            $this->add_single($name, $cont->fields);
        }else{
            if (!Schema::hasTable('tab_'.$name)) {
                $this->create_table($name, $cont->fields, $parent);
            }else{
                $this->alter_table($name, $cont->fields, $parent);
            }
        }
    }

    /**
     * Add or update single doctype fields.
     * 
     * @param string $name Name of the doctype to add fields to.
     * @param array $fields Array of field objects to add to the doctype.
     * @return void
    */
    protected function add_single($name, $fields)
    {
        $docField = [];
        foreach ($fields as $value) {
            // jika fieldtype merupakan field khusus (e.x : 'Table') skip 
            if (in_array($value->fieldtype, $this->doctype['except_field'])){ 
                continue;
            }
            
            Single::updateOrCreate(
                ['doctype' => $name, 'fieldname' => $value->fieldname]
            );

            array_push($docField, $value->fieldname);
        }
        Single::where('doctype', $name)->whereNotIn('fieldname', $docField)->delete();
    }

    /**
     * Create a database table for a document type.
     * 
     * @param string $name The name of the document type to create the table for.
     * @param array $fields An array of field definitions for the document type.
     * @param string|null $parent The name of the parent document type if this document type is a child document type.
     * @return void
    */
    protected function create_table($name, $fields, $parent = null)
    {
        Schema::create('tab_'.$name, function (Blueprint $table) use($name, $fields, $parent) {
            // tambah column
            $table->string('name', VARCHAR_LEN)->primary();
            
            $table->timestamps();
            $table->string('owner', VARCHAR_LEN)->nullable();
            $table->string('modified_by', VARCHAR_LEN)->nullable();

            $table->boolean('docstatus')->default(0);
            $table->string('parent_name', VARCHAR_LEN)->index('parent_name')->nullable();
            $table->string('parent_field', VARCHAR_LEN)->nullable();
            $table->string('parent_type', VARCHAR_LEN)->index('parent_type')->nullable();
            $table->bigInteger('idx')->nullable();

            $this->addColumn($table, $name, $fields);
        });
    }

    /**
     * Alter an existing table in the database.
     *
     * @param string $name The name of the table to alter.
     * @param array $fields The fields to add or modify in the table.
     * @param string|null $parent The name of the parent table, if any.
     * @return void
     */
    protected function alter_table($name, $fields, $parent = null)
    {
        // ubah table column
        Schema::table('tab_'.$name, function (Blueprint $table) use($name, $fields, $parent){
            // tambah column
            $this->addColumn($table, $name, $fields);
        });
    }

    /**
     * Add columns to a table and create/drop index based on fieldtype
     * 
     * @param \Illuminate\Database\Schema\Blueprint $table The table schema object to add columns to
     * @param string $table_name The name of the table
     * @param array $fields The list of fields to add to the table
     * @throws \InvalidArgumentException if a field type is not found in the type map config
     * @return void
    */
    protected function addColumn($table, $table_name, $fields)
    {
        $index = $this->listTableIndexes('tab_'.$table_name);

        foreach ($fields as $value) {
            // jika fieldtype merupakan field khusus (e.x : 'Table') skip 
            if (in_array($value->fieldtype, $this->doctype['except_field'])){ 
                continue;
            }

            // check jika type ada pada file config db_type 
            if (!array_key_exists($value->fieldtype, $this->doctype['type_map'])){
                throw new InvalidArgumentException("Function [".($value->fieldtype)."] not found.");
            }

            [$type, $length, $defaultValue] = $this->type_map($value->fieldtype);
            $column = $table->{$type}($value->fieldname, ...$length);
            
            // set default value pada field
            if((property_exists($value, 'reqd') && $value->reqd == 1) 
                || property_exists($value, 'default') 
                || isset($defaultValue)){
                $column->default($value->default ?? $defaultValue ?? null);
            }else{
                // field bisa bernilai null jika bukan mandatory dan tidak memiliki default value
                $column->nullable();
            }

            if (Schema::hasColumn('tab_'.$table_name, $value->fieldname)) {
                $column->change();
            }

            $is_index = in_array($value->fieldtype, $this->doctype['index_map']);
            $index_exist = in_array($value->fieldname, $index);

            if ($is_index && !$index_exist){ 
                $table->index($value->fieldname, $value->fieldname);
            }elseif(!$is_index && $index_exist){
                $table->dropIndex($value->fieldname);
            }

        }
    }

    /**
     * Get the corresponding database type, length, and default value from the fieldtype
     * 
     * @param string $fieldtype The fieldtype to get the corresponding database type for
     * @throws InvalidArgumentException If the fieldtype is not found in the type map
     * @return array An array containing the corresponding database type, length, and default value
    */
    protected function type_map($fieldtype)
    {
        $data = $this->doctype['type_map'];

        if (!array_key_exists($fieldtype, $data)){
            throw new InvalidArgumentException("Function [".($fieldtype)."] not found.");
        }

        return [
            $data[$fieldtype][0], 
            is_array($data[$fieldtype][1]) ?  $data[$fieldtype][1] : [ $data[$fieldtype][1] ], 
            (array_key_exists(2, $data[$fieldtype]) ? $data[$fieldtype][2] : null)];
    }

    /**
     * Get the list of indexes for the specified table.
     * 
     * @param string $table The name of the table.
     * @return array The array of index names.
    */
    protected function listTableIndexes($table)
    {
        $conn = Schema::getConnection()->getDoctrineSchemaManager();

        return array_map(function($key) {
            return $key->getName();
        }, $conn->listTableIndexes($table));
    }
}
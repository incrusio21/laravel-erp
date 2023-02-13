<?php

namespace Erp\Foundation;

use Erp\Models\Single;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

const VARCHAR_LEN = 140;

class Migrate
{    
    protected $except_field = [];

    protected $index_map = [
        'Link',
    ];

    protected $type_map = [
        "Currency"          => ["decimal", [21,9], 0],
        "Int"               => ["integer", "11"],
        "Long Int"          => ["bigInteger", "20"],
        "Float"             => ["decimal", [21,9], 0],
        "Percent"           => ["decimal", [21,9], 0],
        "Check"             => ["boolean", "", 0],
        "Small Text"        => ["text", ""],
        "Long Text"         => ["longText", ""],
        "Code"              => ["longText", ""],
        "Text Editor"       => ["longText", ""],
        "Markdown Editor"   => ["longText", ""],
        "HTML Editor"       => ["longText", ""],
        "Date"              => ["date", ""],
        "Datetime"          => ["datetime", "6"],
        "Time"              => ["time", "6"],
        "Text"              => ["text", ""],
        "Data"              => ["string", VARCHAR_LEN],
        "Link"              => ["string", VARCHAR_LEN],
        "Dynamic Link"      => ["string", VARCHAR_LEN],
        "Password"          => ["text", ""],
        "Select"            => ["string", VARCHAR_LEN],
        "Rating"            => ["tinyInteger"], "",
        "Read Only"         => ["string", VARCHAR_LEN],
        "Attach"            => ["text", ""],
        "Attach Image"      => ["text", ""],
        "Signature"         => ["longText", ""],
        "Color"             => ["string", VARCHAR_LEN],
        "Barcode"           => ["longText", ""],
        "Geolocation"       => ["longText", ""],
        "Duration"          => ["decimal", [21,9], 0],
        "Icon"              => ["string", VARCHAR_LEN],
    ];

    public function __construct() {
        $this->except_field = config('erp.except_field');
    }

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
        
        flags('document', [$name => $cont]);
        
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

    protected function alter_table($name, $fields, $parent = null)
    {
        // ubah table column
        Schema::table('tab_'.$name, function (Blueprint $table) use($name, $fields, $parent){
            // tambah column
            $this->addColumn($table, $name, $fields);

            // $foreignKeys = $this->listTableForeignKeys('tab_'.$name);
            // $is_exsist = in_array('tab_'.strtolower($name).'_parent_name_foreign', $foreignKeys);
            
            // // cek jika merupakan child table dan hanya memiliki satu parent doctype
            // // if($parent && !$is_exsist){
            // //     $this->addForeign($name, ['foreign' => 'parent_name', 'reference' => 'name', 'parent' => $parent]);
            // // }else if(!$parent && $is_exsist) {
            // //     $table->dropForeign('tab_'.strtolower($name).'_parent_name_foreign');
            // // }
        });
    }

    /**
     * Tambah kolom pada table
     * 
     * @param \Illuminate\Database\Schema\Blueprint $table
     * @param object $cont
     */
    protected function addColumn($table, $table_name, $fields)
    {
        $index = $this->listTableIndexes('tab_'.$table_name);

        foreach ($fields as $value) {
            // jika fieldtype merupakan field khusus (e.x : 'Table') skip 
            if (in_array($value->fieldtype, $this->except_field)){ 
                continue;
            }

            // check jika type ada pada file config db_type 
            if (!array_key_exists($value->fieldtype, $this->type_map)){
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

            $is_index = in_array($value->fieldtype, $this->index_map);
            $index_exist = in_array($value->fieldname, $index);

            if ($is_index && !$index_exist){ 
                $table->index($value->fieldname, $value->fieldname);
            }elseif(!$is_index && $index_exist){
                $table->dropIndex($value->fieldname);
            }

        }
    }

    /**
     * Tambah data pada table single
     * 
     * @param string $name
     * @param object $fields
     */
    protected function add_single($name, $fields)
    {
        $docField = [];
        foreach ($fields as $value) {
            // jika fieldtype merupakan field khusus (e.x : 'Table') skip 
            if (in_array($value->fieldtype, $this->except_field)){ 
                continue;
            }
            
            Single::updateOrCreate(
                ['doctype' => $name, 'fieldname' => $value->fieldname]
            );

            array_push($docField, $value->fieldname);
        }
        Single::where('doctype', $name)->whereNotIn('fieldname', $docField)->delete();
    }

    protected function type_map($fieldtype)
    {
        $data = $this->type_map;

        if (!array_key_exists($fieldtype, $data)){
            throw new InvalidArgumentException("Function [".($fieldtype)."] not found.");
        }

        return [
            $data[$fieldtype][0], 
            is_array($data[$fieldtype][1]) ?  $data[$fieldtype][1] : [ $data[$fieldtype][1] ], 
            (array_key_exists(2, $data[$fieldtype]) ? $data[$fieldtype][2] : null)];
    }

    protected function listTableIndexes($table)
    {
        $conn = Schema::getConnection()->getDoctrineSchemaManager();

        return array_map(function($key) {
            return $key->getName();
        }, $conn->listTableIndexes($table));
    }
}
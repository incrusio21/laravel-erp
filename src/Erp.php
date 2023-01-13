<?php

namespace Erp;

use App\Extensions\BaseDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Model General Untuk Document ERP
 */
class Erp 
{   
    protected $meta;
    
    /**
     * Create a new migration install command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Support\Composer  $composer
     * @return void
     */
    public function __construct()
    {
        $this->meta = app('sysdefault')->require_method('erp.models.meta', 'get_meta');
    }

    /**
     * Ambil data field doctype berdasarkan table docfield
     * 
     * @param mixed $document
     * @param null $parent
     * 
     * @return \App\Extensions\BaseDocument
     */
    protected function new_doc($doctype)
    {
        // cek doctype ada atau tidak
        $document = DB::table($this->getAppTable('docType'))->where('name', $doctype)->first(); //DocType::with('field')->find($doctype);
        if(!$document) erpThrow('DocType tidak ditemukan', 'Not Found');
        
        return new BaseDocument($document);
    }

    /**
    * Ambil data document berdasarkan table doctype
    * 
    * @param string $doctype
    * @param null $name
    * @param array $filter
    * 
    * @return \App\Extensions\BaseDocument
    */
    protected function get_doc($doctype, $name = null, $filter = [])
    {
        // Pesan error jika variable name dan filter kosong
        if(!$name && !$filter){
            erpThrow('Tolong isi Name atau Filter terlebih dahulu', 'Pesan');
        }

        // buat document kosong
        $data = $this->new_doc($doctype);
        
        // get data doctype berdasarkan field pada table docoumen field
        return $data->set_doc_field(($filter ?: ['name' => $name]));
    }

    /**
     * Ambil data field berdasarkan table docfield
     * 
     * @param mixed $document
     * @param null $parent
     * 
     * @return \Illuminate\Support\Collection
     */
    protected function get_doc_field($document, $param, $is_parent = true)
    {
        try {
            // get data document dengan select berdasarkan field name dan where berdasarkan param
            $query = DB::table($document->table_name ?? 'tab_'.$document->name)->select($field_name)->where($param);

            // jika merukapan document utama cukup ambil data pertama, jika tidak ambil smua data
            $data = $is_parent ? $query->first() : $query->get();
            if(!$data) erpThrow('Document tidak ditemukan', 'Not Found');

            // field tambahan jika terdapat field table dll
            if ($data instanceof \Illuminate\Support\Collection){
                foreach ($data as $row){
                    $this->add_fixed_field($row, $document->name, $field_child);
                }
            }else{
                $this->add_fixed_field($data, $document->name, $field_child);
            }
        }catch ( \Illuminate\Database\QueryException $e) {
            erpThrow($e->errorInfo[2], 'Database Error');
        }

        return $data;
    }

    /**
     * Tambahkan field nama doctpye dan child jika ada
     * 
     * @param mixed $document
     * @param null $parent
     * 
     * @return null
     */
    protected function add_fixed_field($row, $doctype, $field_child)
    {
        $row->doctype = $doctype;
        foreach ($field_child as $field_name => $db_child) {
            $row->$field_name = $this->get_doc_field($db_child, ['parent' => $row->name], false);
        }
    }

    protected function doc_list($doctype, $filter = [])
    {
        try {
            // get data document dengan select berdasarkan field name dan where berdasarkan param
            $data = DB::table($doctype->table_name ?? 'tab_'.$doctype->name)->get();
            if(!$data) erpThrow('Document tidak ditemukan', 'Not Found');
        }catch ( \Illuminate\Database\QueryException $e) {
            erpThrow($e->errorInfo[2], 'Database Error');
        }

        return $data;
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {        
        return $this->$method(...$parameters);
    }

    /**
     * Handle dynamic static method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        if (in_array($method, ['new_doc', 'get_doc', 'doc_list', 'get_doc_field'])) {
            return (new static)->$method(...$parameters);   
        }
    }
}
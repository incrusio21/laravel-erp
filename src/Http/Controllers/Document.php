<?php

namespace Erp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Routing\Controller;

class Document extends Controller
{
    /**
     * @var string
     */
    protected $docType;

    /**
     * @var App\Extensions\BaseDocument
     */
    protected $document;

    /**
     * Set the template from the table config file if it exists
     *
     * @param   array   $config (default: array())
     * @return  void
     */
    public function __construct()
    {
        pushData([
            'title' => 'Tindakan Rawat Jalan'
        ]);
        
        $this->docType = ErpRoute::get_doctype();
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $self = Model::doc_list($this->docType->name);

        pushData([
            'title' => '111',
            'table' => 'bbb'
        ]);
        
        method_exists($this, 'on_index') && $on_index = $this->on_index($self);

        return $on_index ?? render('welcome');
    }

    /**
     * @param \Illuminate\Http\Request $request
     * 
     * @return \Illuminate\Http\Response
     */
    public function form(Request $request, $name = null)
    {
        // $this->document =  new BaseDocument($this->docType);
        $fields = $this->fields($this->docType);

        if (request_validator(self::HTTP_TYPE_POST)) {
            $this->on_validated();
            
            switch (post('method')) {
                case 'submit':
                    break;
            }

            return;
        }
        
        // $name && $this->document->set_doc_field(['name' => $name]);
        
        pushData([
            'title' => 'DocType'
        ]);
        
        $on_form = method_exists($this, 'on_form') ? $this->on_form($this->document, $fields) : null;
        
        return $on_form ?? erp_view('app.form', $fields);
    }

    private function fields($json)
    {
        $doc_fields = new Collection($json->fields);
        $field = new Collection([]);
        if (property_exists($json,'field_order')){
            $order = $json->field_order;
            foreach ($order as $fieldname) {
                $ord_field = $doc_fields->firstWhere('fieldname', $fieldname);
                if($ord_field->fieldtype == 'Table'){
                    $child = doctype_json($ord_field->options);
                    $ord_field->child = $this->fields($child);
                }
                $field->push($ord_field);
            }
            
            return $field;
        }

        $field = $doc_fields->map(function ($data) {
            if($data->fieldtype == 'Table'){
                $child = doctype_json($data->options);
                $data->child = $this->fields($child);
            }
            return $data;
        });
        // $this->docType->fields
        
        return $field;
    }

    private function on_validated()
    {
        $validator = \Validator::make($request->all(), [
            'nama'          => 'required',
            'jumlah'        => 'required',
            'subTotal'      => 'required',
            'tanggal'       => 'required',
            'waktu'         => 'required',
        ]);

        // cek validasi request
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // $this->document->set_doc_field(['name' => $name]);

        !method_exists($this, 'validated') ?: $this->validated($this->document);
    }

    private function insert()
    {
        !method_exists($this, 'before_insert') ?: $this->before_insert($this->document);

        !method_exists($this, 'after_insert') ?: $this->after_insert($this->document);
    }

    private function update()
    {
        !method_exists($this, 'on_update') ?: $this->on_update($this->document);
    }

    private function submit()
    {
        !method_exists($this, 'on_submit') ?: $this->on_submit($this->document);
    }
}

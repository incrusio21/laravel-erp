<?php

namespace Erp\Http\Core\DoyType\Doctype;

use Illuminate\Support\Collection;
use Erp\Http\Controllers\Document;

class Controller extends Document
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function on_form($self)
    {
        
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function before_insert($self)
    {
        print_r($self);
    }
}

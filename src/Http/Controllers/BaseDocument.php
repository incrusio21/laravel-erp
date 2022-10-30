<?php

namespace Erp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Routing\Controller;

class BaseDocument extends Controller
{
    function getdoctype(){
        return response()->json([
            'message' => "Data Berhasil Disimpan."
        ]);
    }
}
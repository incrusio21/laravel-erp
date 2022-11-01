<?php

namespace Erp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Routing\Controller;

class BaseDocument extends Controller
{
    function getdoctype(Request $request){
        $validator = \Validator::make($request->all(), [
            'doctype' => 'required',
        ]);

        $docType = doctype_json($request->get('doctype'));

        return response()->json([
            'message' => [$docType]
        ]);
    }
}
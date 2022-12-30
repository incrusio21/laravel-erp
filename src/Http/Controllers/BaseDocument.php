<?php

namespace Erp\Http\Controllers;

use Erp\DocEvents;
use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;

class BaseDocument extends Controller
{
    function getdoctype(Request $request){
        $validator = validator($request->all(), [
            'doctype' => 'required',
        ]);
        
         // cek validasi request
         if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $docType = doctype_json($request->get('doctype'));
        
        return response()->json([
            'docs' => [$docType]
        ]);
    }

    function method(Request $request, $name){
        // $validator = validator($request->all(), [
        //     'method' => 'required',
        // ]);

        //  // cek validasi request
        //  if ($validator->fails()) {
        //     return response()->json($validator->errors(), 400);
        // }

        // $name = $request->post('method');

        return response()->json([
            'message' => app('sysdefault')->call_method($name, [])
        ]);
    }

    function savedoc(Request $request){
        // $validator = validator($request->all(), [
        //     'method' => 'required',
        // ]);

        //  // cek validasi request
        //  if ($validator->fails()) {
        //     return response()->json($validator->errors(), 400);
        // }
        
        // $name = $request->post('method');
        
        DocEvents::dispatch('aaa', 'validated');

        return response()->json([
            'message' => ''
        ]);
    }
}
<?php

namespace Erp;

use Exception as DefaultException;
use Illuminate\Http\Request;

class Exeptions extends DefaultException
{
    public $code;
    public $message;
    
    public function __construct($message, $code, DefaultException $exception = NULL)
    {
        $this->code = $code;
        $this->message = $message;
    }
 
    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function render(Request $request)
    {
        $message = json_decode($this->message, true);

        if($request->ajax()){
            return response()->json(['message' => $message['error']], $this->code);
        }

        return response()->view('errors.erp', ['title' => $message['title'],'message' => $message['error']], $this->code);
    }
}

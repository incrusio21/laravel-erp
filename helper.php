<?php

use Erp\Models\DocType;

use Illuminate\Support\HtmlString;

define('DS', DIRECTORY_SEPARATOR);

if (! function_exists('doctype_json')){
    function doctype_json($docType, $namespace = null)
    {
        // get namespace berdasarkan nama doctype 
        if(!$namespace){
            // cek doctype ada atau tidak
            $document = DocType::with('modules')->find($docType);
            if(!$document) erpThrow('DocType tidak ditemukan', 'Not Found');

            $namespace = $document->modules->namespace;
        }

        try {
            // get doctype json file berdasarkan namespace
            $file = (new \ReflectionClass('\\'.$namespace.'\Controller\\'.$docType.'\Controller'))->getFileName();
            if(!\File::exists($form = str_ireplace('Controller.php', 'form.json', $file))){
                erpThrow('File tidak ditemukan', 'File Not Found');
            }
            return json_decode(\File::get($form)); 
        } catch (\Exception $e) {
            erpThrow('Data Form tidak di temukan', 'Form Not Found');
        }
    }
}

if (! function_exists('doctype_script')){
    
    function doctype_script(): HtmlString
    {   
        $route = config('erp.route');
        
        $config = [
            "app_logo_url" => config('erp.app.logo'),
            'prefix' => [
                'web' => $route['web']['prefix'],
                'api' => $route['api']['prefix']
            ],
            'user' => [
                'can_read' => app('sysdefault')->doctpye_form(function ($docType, $form, $prefix) {
                    // baca meta modul 
                    $cont   = json_decode(\File::get($form));
                    if (!isset($cont->is_child)){
                        return [$docType];
                    }
                })
            ]
        ];
        $boot = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        return new HtmlString(<<<HTML
            <script>
                if (!window.erp) window.erp = {};
    
                erp.boot = $boot
            </script>  
        HTML);
    }
}

if (! function_exists('erpThrow')) {
    function erpThrow($message, $title = 'Error API', $code = 400)
    {
        throw new \Erp\Exeptions(json_encode(['title' => $title, 'error' => $message]), $code);
    }
}
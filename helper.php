<?php

use Erp\ErpForm;
use Erp\Models\DocType;

use Illuminate\Support\HtmlString;

// if (! function_exists('erp_path')) {
//     /**
//      * Get the path to the application folder.
//      *
//      * @param  string  $path
//      * @return string
//      */
//     function app_path($path = '')
//     {
//         return app()->path($path);
//     }
// }
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
            $file = (new \ReflectionClass('\\'.$namespace.'\\'.$docType.'\Controller'))->getFileName();
            if(!\File::exists($form = str_replace('controller.php', 'form.json', $file))){
                erpThrow('File tidak ditemukan', 'File Not Found');
            }
            return json_decode(\File::get($form)); 
        } catch (\Exception $e) {
            erpThrow('Data Form tidak di temukan', 'Form Not Found');
        }
    }
}

function doctype_script(): HtmlString
{   
    $config = [
        "app_logo_url" => config('erp.app.logo'),
        'prefix' => [
            'web' => config('erp.route.web.prefix'),
            'api' => '/'.config('erp.route.api.prefix')
        ],
        'user' => [
            'can_read' => ErpForm::doctpye_form(function ($docType, $form, $prefix) {
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
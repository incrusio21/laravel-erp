<?php

namespace Erp\Traits;

use LogicException;

trait Utils {

    protected $doctypes_to_skip = [
        "Communication",
        "ToDo",
        "DocShare",
        "Email Unsubscribe",
        "Activity Log",
        "File",
        "Version",
        "Document Follow",
        "Comment",
        "View Log",
        "Tag Link",
        "Notification Log",
        "Email Queue",
        "Document Share Key",
    ];

    protected $def_doctype = [
        'App',
        'Apps',
        'Doctype',
        'Docfield',
        'Module'
    ];
    
    protected function get_table_name(string $doctype){
        $table = strtolower(
            !str_starts_with($doctype, '__') ? "tab_{$doctype}" : $doctype
        );

        if($table == config('erp.singles')){
            throw new LogicException('Nama Doctype tidak dapat digunkan');
        }

        return $table;
    }

    protected function appFile($file)
    {
        if($path = config('erp.path')){
            $path .= DS;
        }

        return [$path, config('erp.'.$file)];
    }
}
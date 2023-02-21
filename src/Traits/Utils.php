<?php

namespace Erp\Traits;

use Exception;

trait Utils 
{

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
    
    protected function erpConfig(array $key)
    {
        return app('config')->getMany(array_map(fn($value) => "erp.$value", $key));
    }

    protected function get_table_name(string $doctype)
    {
        $table = strtolower(
            !str_starts_with($doctype, '__') ? "tab_{$doctype}" : $doctype
        );

        if($table == config('erp.singles')){
            throw new Exception('Nama Doctype tidak dapat digunkan');
        }

        return $table;
    }
}
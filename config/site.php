<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ERP Site Public Path
    |--------------------------------------------------------------------------
    |
    | This value is the name of the folder for the ERP site to be created.
    |
    */
    'public_storage' => 'public',

    /*
    |--------------------------------------------------------------------------
    | ERP Site Database Config
    |--------------------------------------------------------------------------
    |
    | This value is the name of the folder for the ERP site to be created.
    |
    */
    'db_connection' => [
        'connection' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'laravel',
        'username' => 'root',
        'password' => ''
    ]
];
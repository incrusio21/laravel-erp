<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ERP Path
    |--------------------------------------------------------------------------
    |
    | This value is the name of the folder for the ERP application to be created.
    |
    */
    'path' => 'erp',
    
    /*
    |--------------------------------------------------------------------------
    | ERP Composer
    |--------------------------------------------------------------------------
    |
    | This value is the name of the composer used to store the list of applications that will be used.
    |
    */
    'composer' => 'composer.json',

    /*
    |--------------------------------------------------------------------------
    | ERP App
    |--------------------------------------------------------------------------
    |
    | This value represents the name of the file used to store the names of all 
    | the applications present in the directory.  
    |
    */
    'app' => 'app.txt',

    /*
    |--------------------------------------------------------------------------
    | ERP Module
    |--------------------------------------------------------------------------
    |
    | This value represents the filename used to store the module names for each application.
    |
    */
    'modules' => 'modules.txt',

    /*
    |--------------------------------------------------------------------------
    | Table Single
    |--------------------------------------------------------------------------
    |
    | The table name for storing data for doctypes that can only have a single data record (is_single).
    |
    */
    'singles' => 'tab_singles',

    /*
    |--------------------------------------------------------------------------
    | ERP Prefix
    |--------------------------------------------------------------------------
    |
    | The prefixes used for the different parts of the ERP system.
    |
    | The 'desktop' key specifies the prefix used for the desktop version of the
    | application, and the 'api' key specifies the prefix used for the API routes.
    |
    */
    'prefix' => [
        'desktop' => 'app',
        'api' => 'api'
    ],
];
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ERP Route
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the route used to determine and
    | manage Route Web App based ERP.
    |
    */
    'app' => [
        'name' => 'app',
        'logo' => "/assets/erpnext/images/erpnext-logo.svg",
        'path' => 'erp',
        'filename' => 'app.json',
        'modules' => [
            'laravel_app' => 'Modules',
            'installed_app' => 'Http'
        ],
    ],

    
    /*
    |--------------------------------------------------------------------------
    | ERP Route
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the route used to determine and
    | manage Route Web App based ERP.
    |
    */
    'route' => [
        'web' => [
            'prefix' => 'app'
        ],
        'api' => [
            'prefix' => 'api',
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | ERP Default Table
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the database driver used to determine and
    | manage ERP Document.
    |
    */
    'table' => [
        'app' => 'tab_app',
        'module' => 'tab_module',
        'docType' => 'tab_docType',
        'docType_field' => 'tab_docfield',
    ]
];
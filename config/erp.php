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
        'logo' => "/assets/erpnext/images/erpnext-logo.svg",
        'module' => [
            app_path('Modules') => 'App\Modules'
        ],
        'installed_app' => 'app.json'
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
        'module' => 'tab_module',
        'docType' => 'tab_docType',
        'docType_field' => 'tab_docfield',
    ]
];
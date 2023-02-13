<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ERP Path
    |--------------------------------------------------------------------------
    |
    | nilai ini merupakan nama folder dari aplikasi erp yang akan di buat. 
    |
    */
    'path' => 'erp',
    
    /*
    |--------------------------------------------------------------------------
    | ERP Composer
    |--------------------------------------------------------------------------
    |
    | nilai ini merupakan nama composer yang digunakan untuk menyimpan daftar aplikasi
    | yang akan digunakan  
    |
    */
    'composer' => 'composer.json',

    /*
    |--------------------------------------------------------------------------
    | ERP App
    |--------------------------------------------------------------------------
    |
    | nilai ini merupakan nama file yang digunakan untuk menyimpan nama semua app yang 
    | ada pada direktori  
    |
    */
    'app' => 'app.txt',

    /*
    |--------------------------------------------------------------------------
    | ERP Module
    |--------------------------------------------------------------------------
    |
    | nilai ini merupakan nama file yang digunakan untuk menyimpan nama module yang 
    | ada pada setiap app    
    |
    */
    'modules' => 'modules.txt',

    /*
    |--------------------------------------------------------------------------
    | Default App
    |--------------------------------------------------------------------------
    |
    | nilai ini merupakan nama default app yang dapat dipanggil tanpa harus di simpan 
    | pada database
    |
    */
    'default_app' => [
        'name' => 'erp',
        'path' => __DIR__.DS.'..'.DS.'src',
        'namespace' =>  'Erp\\'
    ],

    /*
    |--------------------------------------------------------------------------
    | ERP Prefix
    |--------------------------------------------------------------------------
    |
    */
    'prefix' => [
        'desktop'   => 'app',
        'api'       => 'api'
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Single
    |--------------------------------------------------------------------------
    |
    | nama table untuk menyimpan data untuk doctype yang hanya boleh memiliki satu
    | data (is_single) 
    |
    */
    'singles' => 'tab_singles',

    /*
    |--------------------------------------------------------------------------
    | ERP Except Doctype
    |--------------------------------------------------------------------------
    |
    | daftar nama doctype yang tidak dapat digunakan
    |
    */
    'except_doc' => [
        'Singles'
    ],

    /*
    |--------------------------------------------------------------------------
    | ERP Except Doctype
    |--------------------------------------------------------------------------
    |
    | daftar nama field yang bukan merupan nama column pada database
    |
    */
    'except_field' => [
        'Table',
    ]
];
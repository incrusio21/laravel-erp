<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Except Doctype Fieldtype
    |--------------------------------------------------------------------------
    |
    | The following is an array of field names that do not correspond to database column names:
    |
    */
    'except_field' => [
        'Table',
    ],

    /*
    |--------------------------------------------------------------------------
    | Index Doctype Fieldtype
    |--------------------------------------------------------------------------
    |
    | This is a list of field types whose corresponding column names in the database table have an index.
    |
    */
    'index_map' => [
        'Link',
    ],

    /*
    |--------------------------------------------------------------------------
    | Doctype Fieldtype List
    |--------------------------------------------------------------------------
    |
    |
    */
    'data_fieldtypes' => [
        "Currency",
        "Int",
        "Long Int",
        "Float",
        "Percent",
        "Check",
        "Small Text",
        "Long Text",
        "Code",
        "Text Editor",
        "Markdown Editor",
        "HTML Editor",
        "Date",
        "Datetime",
        "Time",
        "Text",
        "Data",
        "Link",
        "Dynamic Link",
        "Password",
        "Select",
        "Rating",
        "Read Only",
        "Attach",
        "Attach Image",
        "Signature",
        "Color",
        "Barcode",
        "Geolocation",
        "Duration",
        "Icon",
    ],

    /*
    |--------------------------------------------------------------------------
    | Doctype Fieldtype List Not Column in Table
    |--------------------------------------------------------------------------
    |
    |
    */
    'table_fields' => [
        "Table", "Table MultiSelect"
    ],
    /*
    |--------------------------------------------------------------------------
    | Doctype Type Map
    |--------------------------------------------------------------------------
    |
    | This refers to the mapping of data types used in a doctype (a document type in the ERP) to their corresponding data types in the database table 
    | that stores the document's data. The list specifies how each doctype field type should be converted to its respective database field type
    |
    */
    'type_map' => [
        "Currency"          => ["decimal", [21,9], 0],
        "Int"               => ["integer", "11"],
        "Long Int"          => ["bigInteger", "20"],
        "Float"             => ["decimal", [21,9], 0],
        "Percent"           => ["decimal", [21,9], 0],
        "Check"             => ["boolean", "", 0],
        "Small Text"        => ["text", ""],
        "Long Text"         => ["longText", ""],
        "Code"              => ["longText", ""],
        "Text Editor"       => ["longText", ""],
        "Markdown Editor"   => ["longText", ""],
        "HTML Editor"       => ["longText", ""],
        "Date"              => ["date", ""],
        "Datetime"          => ["datetime", "6"],
        "Time"              => ["time", "6"],
        "Text"              => ["text", ""],
        "Data"              => ["string", VARCHAR_LEN],
        "Link"              => ["string", VARCHAR_LEN],
        "Dynamic Link"      => ["string", VARCHAR_LEN],
        "Password"          => ["text", ""],
        "Select"            => ["string", VARCHAR_LEN],
        "Rating"            => ["tinyInteger"], "",
        "Read Only"         => ["string", VARCHAR_LEN],
        "Attach"            => ["text", ""],
        "Attach Image"      => ["text", ""],
        "Signature"         => ["longText", ""],
        "Color"             => ["string", VARCHAR_LEN],
        "Barcode"           => ["longText", ""],
        "Geolocation"       => ["longText", ""],
        "Duration"          => ["decimal", [21,9], 0],
        "Icon"              => ["string", VARCHAR_LEN],
    ],

    /*
    |--------------------------------------------------------------------------
    | Doctype Default Fields
    |--------------------------------------------------------------------------
    |
    | This array defines a set of default fields for a document type. The fields
    | are listed in the order in which they should appear by default.
    */
    'default_fields' => [
        "doctype",
        "name",
        "owner",
        "modified_by",
        "parent_name",
        "parent_field",
        "parent_type",
        "idx",
        "docstatus",
    ]
];
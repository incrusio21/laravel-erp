<?php

namespace Erp\Traits;

trait Models {
    protected $data_fieldtypes = [
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
    ];

    protected $table_fields = [
        "Table", "Table MultiSelect"
    ];

    protected $default_fields = [
        "doctype",
        "name",
        "owner",
        "modified_by",
        "parent_name",
        "parent_field",
        "parent_type",
        "idx",
        "docstatus",
    ];
}
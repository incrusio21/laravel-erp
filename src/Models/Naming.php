<?php

namespace Erp\Models;

class Naming
{
    protected function set_new_name($doc)
    {
        $autoname = $doc->meta->autoname ?? "";

        if(property_exists($doc, 'amended_from')){
            _set_amended_name($doc);
        }else if(property_exists($doc->meta, 'is_single')){
            $doc->name = $doc->doctype;
        }

        // at this point, we fall back to name generation with the hash option
        if (!$doc->name && $autoname == "hash"){
            $doc->name = app('erp')->generate_hash($doc->doctype, 10);
        }
    }

    public function __call(string $method, array $parameters)
    {
        return $this->$method(...$parameters);
    }

    public static function __callStatic(string $method, array $parameters) {
        // Note: value of $name is case sensitive.
        return (new static)->$method(...$parameters);   
    }
}
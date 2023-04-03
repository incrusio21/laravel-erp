<?php

namespace LaravelErp\Models;

class Naming
{
    /**
     * Set a new name for the document.
     *
     * @param object $doc The document to set the name for.
     */
    protected function set_new_name(object $doc) : void
    {
        $autoname = $doc->meta->autoname ?? "";

        if(property_exists($doc, 'amended_from')){
            _set_amended_name($doc);
        }else if(property_exists($doc->meta, 'is_single')){
            $doc->name = $doc->doctype;
        }
        // at this point, we fall back to name generation with the hash option
        if (!$doc->name && $autoname == "hash"){
            $doc->name = generate_hash($doc->doctype, 10);
        }
    }

    /**
     * Magic method to call class methods dynamically.
     *
     * @param string $method The name of the method being called.
     * @param array $parameters The parameters to pass to the method being called.
     */
    public function __call(string $method, array $parameters) : mixed
    {
        return $this->$method(...$parameters);
    }

    /**
     * Magic method to call class methods statically.
     *
     * @param string $method The name of the method being called.
     * @param array $parameters The parameters to pass to the method being called.
     */
    public static function __callStatic(string $method, array $parameters) : mixed
    {
        // Note: value of $name is case sensitive.
        return (new static)->$method(...$parameters);   
    }
}
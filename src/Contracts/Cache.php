<?php

namespace Erp\Contracts;

use Illuminate\Support\Facades\Cache as LaravelCache;

class Cache
{
    /**
     * The data stored in the cache.
     * @var array
    */
    public array $data;

    /**
     * Create a new Cache instance.
     * @param string $name The name of the cache to create or retrieve.
     * @return void
    */
    public function __construct(public string $name = '')
    {   
        $this->data = LaravelCache::get($name) ?? [];
    }

    /**
     * Magic method to retrieve a property from the data array.
     * 
     * @param string $property The name of the property to retrieve.
    */
    public function __get(string $property) : mixed 
    {
        if (!array_key_exists($property, $this->data)) {
            return null;
        }

        return $this->data[$property];
    }

    /**
     * Magic method to set a property in the data array and store it in the cache.
     * 
     * @param string $property The name of the property to set.
     * @param mixed $value The value to set the property to.
    */
    public function __set(string $property, mixed $value) : void
    {
        $this->data[$property] = $value;

        LaravelCache::forever($this->name, $this->data);
    }
}
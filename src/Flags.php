<?php

namespace Erp;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class Flags
{
    protected $cache;

    /**
     * Create a new migration install command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Support\Composer  $composer
     * @return void
     */
    public function __construct(array $cache)
    {   
        $this->cache = $cache;
    }

    public function get(string $name, $key, $default = null)
    {
        if(array_key_exists($name, $this->cache)){
            return Arr::get($this->cache[$name], $key, $default);
        }
        
        return $default;
    }

    /**
     * Set a given configuration value.
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return void
     */
    public function set($name, $key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];
        
        if(!array_key_exists($name, $this->cache)){
            $this->cache += [$name => []];
        }

        foreach ($keys as $key => $value) {
            Arr::set($this->cache[$name], $key, $value);

            Cache::forever('flags', $this->cache);
        }
    }
}
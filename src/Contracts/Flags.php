<?php

namespace Erp\Contracts;

use Illuminate\Support\Facades\Cache;

class Flags
{
    public $data = array();

    /**
     * Create a new migration install command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Support\Composer  $composer
     * @return void
     */
    public function __construct()
    {   
        $this->data = Cache::get('flags') ?? [];
    }

    public function __get($property) {
        if (!array_key_exists($property, $this->data)) {
            return null;
        }

        return $this->data[$property];
    }

    public function __set($property, $value) {
        $this->data[$property] = $value;

        Cache::forever('flags', $this->data);
    }
}
<?php

namespace Erp;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ErpSetup
{
    protected $form_list = [];
    /**
     * @var array<int, class-string<\Illuminate\Console\Command>>
     */
    public const DS = DIRECTORY_SEPARATOR;

    /**
     * @return null
     */
    protected function doctpye_form($calback = false)
    {
        if(\File::exists($composer = base_path('composer.json'))) {
            $file   = json_decode(\File::get($composer));
            
            \File::put($composer, json_encode($file, JSON_PRETTY_PRINT));
            $this->composer->dumpAutoloads();
        }
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->$method(...$parameters);
    }
    
    /**
     * Handle dynamic static method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        if (in_array($method, ['setup'])) {
            return (new static)->$method(...$parameters);   
        }
    }
}
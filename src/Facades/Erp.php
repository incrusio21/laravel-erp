<?php

namespace LaravelErp\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string version()
 *
 * @see \LaravelErp\Init
 */
class Erp extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-erp';
    }
}
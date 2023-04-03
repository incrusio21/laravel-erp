<?php

namespace LaravelErp\Core\Controllers\App;

use Illuminate\Support\Collection;
use LaravelErp\Modules\Document;

class Controller extends Document
{
    protected function setTable()
    {
        $this->table = config('erp.installed_app');
    }
}

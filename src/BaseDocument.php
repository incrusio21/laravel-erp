<?php

namespace Erp;

use App\Models\DocType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BaseDocument
{
    /**
     * @var string
     */
    protected $table;

    /**
     * Set the template from the table config file if it exists
     *
     * @param   array   $config (default: array())
     * @return  void
     */
    public function __construct($document = null)
    {
        $doc_event = app('doc_event');
    }
}
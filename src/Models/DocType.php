<?php

namespace Erp\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocType extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $primaryKey = 'name';

    /**
     * @var bool
     */
    public $incrementing = false;

    public function __construct() {
        parent::__construct();
        $this->setTable(config('erp.table.docType'));
    }

    /**
     * Get the field Doctype.
     */
    public function field()
    {
        return $this->hasMany(DocField::class, 'parent', 'name');
    }

    /**
     * Get the module Doctype.
     */
    public function modules()
    {
        return $this->belongsTo(Module::class, 'module', 'name');
    }
}

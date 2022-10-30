<?php

namespace Erp\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocField extends Model
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
        $this->setTable(config('erp.table.docType_field'));
    }

    /**
     * Get the doctype parent Docfield.
     */
    public function doctype()
    {
        return $this->belongsTo(DocType::class, 'parent', 'name');
    }
}

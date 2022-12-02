<?php

namespace Erp\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'namespace',
        'app',
    ];

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
        $this->setTable(config('erp.table.module'));
    }

    /**
     * Get the field Doctype.
     */
    public function doctype()
    {
        return $this->hasMany(DocType::class, 'module', 'name');
    }

    /**
     * Get the module Doctype.
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app', 'name');
    }
}

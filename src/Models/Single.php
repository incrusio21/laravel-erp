<?php

namespace LaravelErp\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Single extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'doctype',
        'fieldname',
        'value',
    ];

    /**
     * @var string
     */
    protected $primaryKey = null;

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * Single constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->setTable(config('erp.singles'));
    }
}
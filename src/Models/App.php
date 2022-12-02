<?php

namespace Erp\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class App extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'versi'
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
        $this->setTable(config('erp.table.app'));
    }

    /**
     * Get the field Modlule.
     */
    public function module()
    {
        return $this->hasMany(Module::class, 'app', 'name');
    }
}

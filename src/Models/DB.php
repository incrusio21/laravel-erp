<?php

namespace Erp\Models;

use Erp\Traits\Utils;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class DB extends Model
{
    use \Erp\Traits\Utils, 
        HasFactory;
    
    /**
     * @var string
     */
    protected $primaryKey = 'name';
    
    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string
     */
    public $doc;

    public function __construct() {
        $this->fillable = array_slice(config('doctype.default_fields'), 1);

        parent::__construct();
    }

    public function scopeDoc($query, $table_name)
    {
        return $this->setDoc($table_name);
    }
    
    /**
     * Create a new instance of the given model.
     *
     * @param  array  $attributes
     * @param  bool  $exists
     * @return static
     */
    public function newInstance($attributes = [], $exists = false)
    {
        // This method just provides a convenient way for us to generate fresh model
        // instances of this current model. It is particularly useful during the
        // hydration of new objects via the Eloquent query builder instances.
        $model = new static;

        $model->exists = $exists;

        $model->setConnection(
            $this->getConnectionName()
        );

        $model->setDoc($this->doc);
        
        $model->mergeCasts($this->casts);

        $model->fill((array) $attributes);

        return $model;
    }
    
    /**
     * Set the doctype, table and fillable associated with the model.
     *
     * @param  string  $doctype
     * @return $this
     */
    public function setDoc($doctype)
    {
        $this->doc = $doctype;

        $this->table = $this->get_table_name($this->doc);

        if(!property_exists(app('erp')->flags, 'fillabel')){
            app('erp')->flags->fillabel = [];
        } 

        if(!array_key_exists($this->doc, app('erp')->flags->fillabel)) {
            app('erp')->flags->fillabel += [$this->doc => in_array($this->doc, $this->def_doctype) ? $this->getConnection()->getSchemaBuilder()->getColumnListing($this->table) : []];
        }

        $this->fillable(array_unique(array_merge($this->fillable, app('erp')->flags->fillabel[$this->doc])));

        return $this;
    }
}

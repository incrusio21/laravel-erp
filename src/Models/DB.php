<?php

namespace Erp\Models;

use Erp\Traits\Utils;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class DB extends Model
{
    use \Erp\Traits\Models, 
        Utils, 
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
        $this->fillable = array_slice($this->default_fields, 1);

        parent::__construct();
    }

    public static function doc($table_name)
    {
        return (new static)->setDoc($table_name);
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

        if (!$fillable = flags('fillabel', $this->doc)){
            flags('fillabel', [
                $this->doc => in_array($this->doc, $this->def_doctype) ? $this->getConnection()->getSchemaBuilder()->getColumnListing($this->table) : []
            ]);

            $fillable = flags('fillabel', $this->doc);
        }

        $this->fillable(array_unique(array_merge($this->fillable, $fillable)));

        return $this;
    }
}

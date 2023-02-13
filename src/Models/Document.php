<?php

namespace Erp\Models;

use Erp\Traits\Utils;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class Document extends Model
{
    use Utils, HasFactory;
    
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

    // public function __construct() {
    //     $this->fillable = $this->default_column;

    //     parent::__construct();
    // }

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

        if (!$fillable = flags('fillabel', $this->doc)){
            flags('fillabel', [
                $this->doc => in_array($this->doc, $this->def_doctype) ? $this->getConnection()->getSchemaBuilder()->getColumnListing($this->table) : []
            ]);

            $fillable = flags('fillabel', $this->doc);
        }

        $this->fillable(array_unique(array_merge($this->fillable, $fillable)));
        
        return $this;
    }

    /**
     * Save the model to the database.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = [])
    {
        $this->mergeAttributesFromCachedCasts();

        $query = $this->newModelQuery();

        // If the "saving" event returns false we'll bail out of the save and return
        // false, indicating that the save failed. This provides a chance for any
        // listeners to cancel save operations if validations fail or whatever.
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        // If the model already exists in the database we can just update our record
        // that is already in this database using the current IDs in this "where"
        // clause to only update this model. Otherwise, we'll just insert them.
        if ($this->exists) {
            $saved = $this->isDirty() ?
                $this->performUpdate($query) : true;
        }

        // If the model is brand new, we'll insert it into our database and set the
        // ID attribute on the model to the value of the newly inserted row's ID
        // which is typically an auto-increment value managed by the database.
        else {
            $saved = $this->performInsert($query);

            if (! $this->getConnectionName() &&
                $connection = $query->getConnection()) {
                $this->setConnection($connection->getName());
            }
        }

        // If the model is successfully saved, we need to do a few more things once
        // that is done. We will call the "saved" method here to run any actions
        // we need to happen after a model gets successfully saved right here.
        if ($saved) {
            $this->finishSave($options);
        }

        return $saved;
    }
    
    /**
     * Delete the model from the database.
     *
     * @return bool|null
     *
     * @throws \LogicException
     */
    public function delete()
    {
        $this->mergeAttributesFromCachedCasts();

        if (is_null($this->getKeyName())) {
            throw new LogicException('No primary key defined on model.');
        }

        // if (! is_null($instance = $this->where($attributes)->first())) {
        //     return $instance;
        // }

        // return $this->newModelInstance(array_merge($attributes, $values));
        
        $link_fields = $this->get_link_fields($this->doc);

        foreach ($this->get() as $value) {
            // link_fields = [[lf["parent"], lf["fieldname"], lf["issingle"]] for lf in link_fields]
    
            // for link_dt, link_field, issingle in link_fields:
            
            foreach ($link_fields as [$link_dt, $link_field]) {
                $items = doc_model($link_dt)->select(["name", "parent_name", "parent_type", "docstatus"])->where($link_field, $value->name)->get();
                foreach ($items as $item) {
                    $linked_doctype = $item->parent_name ? $item->parent_type : $link_dt;
                    if (in_array($linked_doctype, $this->doctypes_to_skip)){
                        continue;
                    }
                    
                    if ($link_dt == $value->doctype && ($item->parent ?: $item->name) == $value->name){
                        # linked to same item or doc having same name as the item
                        continue;
                    }else{
                        $reference_docname = $item->parent ?: $item->name;
                        // doc_link = '<a href="/app/Form/{0}/{1}">{1}</a>'.format(doc.doctype, doc.name)
                        // "Cannot delete or cancel because {$this->doctype} {$this->doc_link} is linked with {$doc_link} {$this->reference_docname} {$this->row}"
                        throw new LogicException("Cannot delete or cancel because {0} {1} is linked with {2} {3} {4}");
                    }
                }
            }

            // If the model doesn't exist, there is nothing to delete so we'll just return
            // immediately and not do anything else. Otherwise, we will continue with a
            // deletion process on the model, firing the proper events, and so forth.
            if (! $value->exists) {
                return;
            }
    
            if ($value->fireModelEvent('deleting') === false) {
                return false;
            }
    
            // Here, we'll touch the owning models, verifying these timestamps get updated
            // for the models. This will allow any caching to get broken on the parents
            // by the timestamp. Then we will go ahead and delete the model instance.
            $value->touchOwners();
    
            $value->performDeleteOnModel();
    
            // Once the model has been deleted, we will fire off the deleted event so that
            // the developers may hook into post-delete operations. We will then return
            // a boolean true as the delete is presumably successful on the database.
            $value->fireModelEvent('deleted', false);
    
            return true;
        }
    }
}

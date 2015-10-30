<?php

namespace Parse;

use Exception;
use Parse\Internal\ParseRelationOperation;

/**
 * ParseRelation - A class that is used to access all of the children of a many-to-many relationship. Each instance
 * of ParseRelation is associated with a particular parent object and key.
 *
 * @author Mohamed Madbouli <mohamedmadbouli@fb.com>
 */
class ParseRelation
{
    /**
     * The parent of this relation.
     *
     * @var ParseObject
     */
    private $parent;

    /**
     * The key of the relation in the parent object.
     *
     * @var string
     */
    private $key;

    /**
     * The className of the target objects.
     *
     * @var string
     */
    private $targetClassName;

    /**
     * Creates a new Relation for the given parent object, key and class name of target objects.
     *
     * @param ParseObject $parent          The parent of this relation.
     * @param string      $key             The key of the relation in the parent object.
     * @param string      $targetClassName The className of the target objects.
     */
    public function __construct($parent, $key, $targetClassName = null)
    {
        $this->parent = $parent;
        $this->key = $key;
        $this->targetClassName = $targetClassName;
    }

    /**
     * Makes sure that this relation has the right parent and key.
     *
     * @param $parent
     * @param $key
     *
     * @throws \Exception
     */
    private function ensureParentAndKey($parent, $key)
    {
        if (!$this->parent) {
            $this->parent = $parent;
        }
        if (!$this->key) {
            $this->key = $key;
        }
        if ($this->parent !== $parent) {
            throw new Exception('Internal Error. Relation retrieved from two different Objects.');
        }
        if ($this->key !== $key) {
            throw new Exception('Internal Error. Relation retrieved from two different keys.');
        }
    }

    /**
     * Adds a ParseObject or an array of ParseObjects to the relation.
     *
     * @param mixed $objects The item or items to add.
     */
    public function add($objects)
    {
        if (!is_array($objects)) {
            $objects = [$objects];
        }
        $operation = new ParseRelationOperation($objects, null);
        $this->targetClassName = $operation->_getTargetClass();
        $this->parent->_performOperation($this->key, $operation);
    }

    /**
     * Removes a ParseObject or an array of ParseObjects from this relation.
     *
     * @param mixed $objects The item or items to remove.
     */
    public function remove($objects)
    {
        if (!is_array($objects)) {
            $objects = [$objects];
        }
        $operation = new ParseRelationOperation(null, $objects);
        $this->targetClassName = $operation->_getTargetClass();
        $this->parent->_performOperation($this->key, $operation);
    }

    /**
     * Returns the target classname for the relation.
     *
     * @return string
     */
    public function getTargetClass()
    {
        return $this->targetClassName;
    }

    /**
     * Set the target classname for the relation.
     *
     * @param $className
     */
    public function setTargetClass($className)
    {
        $this->targetClassName = $className;
    }

    /**
     * Set the parent object for the relation.
     *
     * @param $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * Gets a query that can be used to query the objects in this relation.
     *
     * @return ParseQuery That restricts the results to objects in this relations.
     */
    public function getQuery()
    {
        $query = new ParseQuery($this->targetClassName);
        $query->relatedTo('object', $this->parent->_toPointer());
        $query->relatedTo('key', $this->key);

        return $query;
    }
}

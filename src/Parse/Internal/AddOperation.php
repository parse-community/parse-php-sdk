<?php

namespace Parse\Internal;

use Parse\ParseClient;
use Parse\ParseException;

/**
 * Class AddOperation - FieldOperation for adding object(s) to array fields.
 *
 * @author Fosco Marotto <fjm@fb.com>
 */
class AddOperation implements FieldOperation
{
    /**
     * Array with objects to add.
     *
     * @var array
     */
    private $objects;

    /**
     * Creates an AddOperation with the provided objects.
     *
     * @param array $objects Objects to add.
     *
     * @throws ParseException
     */
    public function __construct($objects)
    {
        if (!is_array($objects)) {
            throw new ParseException("AddOperation requires an array.");
        }
        $this->objects = $objects;
    }

    /**
     * Gets the objects for this operation.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->objects;
    }

    /**
     * Returns associative array representing encoded operation.
     *
     * @return array
     */
    public function _encode()
    {
        return ['__op' => 'Add',
            'objects'  => ParseClient::_encode($this->objects, true), ];
    }

    /**
     * Takes a previous operation and returns a merged operation to replace it.
     *
     * @param FieldOperation $previous Previous operation.
     *
     * @throws ParseException
     *
     * @return FieldOperation Merged operation.
     */
    public function _mergeWithPrevious($previous)
    {
        if (!$previous) {
            return $this;
        }
        if ($previous instanceof DeleteOperation) {
            return new SetOperation($this->objects);
        }
        if ($previous instanceof SetOperation) {
            $oldList = $previous->getValue();

            return new SetOperation(
                array_merge((array) $oldList, (array) $this->objects)
            );
        }
        if ($previous instanceof AddOperation) {
            $oldList = $previous->getValue();

            return new SetOperation(
                array_merge((array) $oldList, (array) $this->objects)
            );
        }
        throw new ParseException(
            'Operation is invalid after previous operation.'
        );
    }

    /**
     * Applies current operation, returns resulting value.
     *
     * @param mixed  $oldValue Value prior to this operation.
     * @param mixed  $obj      Value being applied.
     * @param string $key      Key this operation affects.
     *
     * @return array
     */
    public function _apply($oldValue, $obj, $key)
    {
        if (!$oldValue) {
            return $this->objects;
        }

        return array_merge((array) $oldValue, (array) $this->objects);
    }
}

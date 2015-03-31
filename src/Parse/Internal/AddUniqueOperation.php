<?php

namespace Parse\Internal;

use Parse\ParseClient;
use Parse\ParseException;

/**
 * Class AddUniqueOperation - Operation to add unique objects to an array key.
 *
 * @author Fosco Marotto <fjm@fb.com>
 */
class AddUniqueOperation implements FieldOperation
{
    /**
     * Array containing objects to add.
     *
     * @var array
     */
    private $objects;

    /**
     * Creates an operation for adding unique values to an array key.
     *
     * @param array $objects Objects to add.
     *
     * @throws ParseException
     */
    public function __construct($objects)
    {
        if (!is_array($objects)) {
            throw new ParseException("AddUniqueOperation requires an array.");
        }
        $this->objects = $objects;
    }

    /**
     * Returns the values for this operation.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->objects;
    }

    /**
     * Returns an associative array encoding of this operation.
     *
     * @return array
     */
    public function _encode()
    {
        return ['__op' => 'AddUnique',
            'objects'  => ParseClient::_encode($this->objects, true), ];
    }

    /**
     * Merge this operation with the previous operation and return the result.
     *
     * @param FieldOperation $previous Previous Operation.
     *
     * @throws ParseException
     *
     * @return FieldOperation Merged Operation.
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
            $oldValue = $previous->getValue();
            $result = $this->_apply($oldValue, null, null);

            return new SetOperation($result);
        }
        if ($previous instanceof AddUniqueOperation) {
            $oldList = $previous->getValue();
            $result = $this->_apply($oldList, null, null);

            return new AddUniqueOperation($result);
        }
        throw new ParseException(
            'Operation is invalid after previous operation.'
        );
    }

    /**
     * Apply the current operation and return the result.
     *
     * @param mixed  $oldValue Value prior to this operation.
     * @param array  $obj      Value being applied.
     * @param string $key      Key this operation affects.
     *
     * @return array
     */
    public function _apply($oldValue, $obj, $key)
    {
        if (!$oldValue) {
            return $this->objects;
        }
        if (!is_array($oldValue)) {
            $oldValue = (array) $oldValue;
        }
        foreach ($this->objects as $object) {
            if ($object instanceof ParseObject && $object->getObjectId()) {
                if (!$this->isParseObjectInArray($object, $oldValue)) {
                    $oldValue[] = $object;
                }
            } elseif (is_object($object)) {
                if (!in_array($object, $oldValue, true)) {
                    $oldValue[] = $object;
                }
            } else {
                if (!in_array($object, $oldValue, true)) {
                    $oldValue[] = $object;
                }
            }
        }

        return $oldValue;
    }

    private function isParseObjectInArray($parseObject, $oldValue)
    {
        foreach ($oldValue as $object) {
            if ($object instanceof ParseObject && $object->getObjectId() != null) {
                if ($object->getObjectId() == $parseObject->getObjectId()) {
                    return true;
                }
            }
        }

        return false;
    }
}

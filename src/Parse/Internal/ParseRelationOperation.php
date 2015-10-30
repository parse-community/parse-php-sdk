<?php

namespace Parse\Internal;

use Exception;
use Parse\ParseClient;
use Parse\ParseObject;
use Parse\ParseRelation;

/**
 * ParseRelationOperation - A class that is used to manage ParseRelation changes such as object add or remove.
 *
 * @author Mohamed Madbouli <mohamedmadbouli@fb.com>
 */
class ParseRelationOperation implements FieldOperation
{
    /**
     * The className of the target objects.
     *
     * @var string
     */
    private $targetClassName;

    /**
     * Array of objects to add to this relation.
     *
     * @var array
     */
    private $relationsToAdd = [];

    /**
     * Array of objects to remove from this relation.
     *
     * @var array
     */
    private $relationsToRemove = [];

    public function __construct($objectsToAdd, $objectsToRemove)
    {
        $this->targetClassName = null;
        $this->relationsToAdd['null'] = [];
        $this->relationsToRemove['null'] = [];
        if ($objectsToAdd !== null) {
            $this->checkAndAssignClassName($objectsToAdd);
            $this->addObjects($objectsToAdd, $this->relationsToAdd);
        }
        if ($objectsToRemove !== null) {
            $this->checkAndAssignClassName($objectsToRemove);
            $this->addObjects($objectsToRemove, $this->relationsToRemove);
        }
        if ($this->targetClassName === null) {
            throw new Exception('Cannot create a ParseRelationOperation with no objects.');
        }
    }

    /**
     * Helper function to check that all passed ParseObjects have same class name
     * and assign targetClassName variable.
     *
     * @param array $objects ParseObject array.
     *
     * @throws \Exception
     */
    private function checkAndAssignClassName($objects)
    {
        foreach ($objects as $object) {
            if ($this->targetClassName === null) {
                $this->targetClassName = $object->getClassName();
            }
            if ($this->targetClassName != $object->getClassName()) {
                throw new Exception('All objects in a relation must be of the same class.');
            }
        }
    }

    /**
     * Adds an object or array of objects to the array, replacing any
     * existing instance of the same object.
     *
     * @param array $objects   Array of ParseObjects to add.
     * @param array $container Array to contain new ParseObjects.
     */
    private function addObjects($objects, &$container)
    {
        if (!is_array($objects)) {
            $objects = [$objects];
        }
        foreach ($objects as $object) {
            if ($object->getObjectId() == null) {
                $container['null'][] = $object;
            } else {
                $container[$object->getObjectID()] = $object;
            }
        }
    }

    /**
     * Removes an object (and any duplicate instances of that object) from the array.
     *
     * @param array $objects   Array of ParseObjects to remove.
     * @param array $container Array to remove from it ParseObjects.
     */
    private function removeObjects($objects, &$container)
    {
        if (!is_array($objects)) {
            $objects = [$objects];
        }
        $nullObjects = [];
        foreach ($objects as $object) {
            if ($object->getObjectId() == null) {
                $nullObjects[] = $object;
            } else {
                unset($container[$object->getObjectID()]);
            }
        }
        if (!empty($nullObjects)) {
            self::removeElementsFromArray($nullObjects, $container['null']);
        }
    }

    /**
     * Applies the current operation and returns the result.
     *
     * @param mixed  $oldValue Value prior to this operation.
     * @param mixed  $object   Value for this operation.
     * @param string $key      Key to perform this operation on.
     *
     * @throws \Exception
     *
     * @return mixed Result of the operation.
     */
    public function _apply($oldValue, $object, $key)
    {
        if ($oldValue == null) {
            return new ParseRelation($object, $key, $this->targetClassName);
        } elseif ($oldValue instanceof ParseRelation) {
            if ($this->targetClassName != null
                && $oldValue->getTargetClass() !== $this->targetClassName
            ) {
                throw new Exception(
                    'Related object object must be of class '
                    .$this->targetClassName.', but '.$oldValue->getTargetClass()
                    .' was passed in.'
                );
            }

            return $oldValue;
        } else {
            throw new Exception('Operation is invalid after previous operation.');
        }
    }

    /**
     * Merge this operation with a previous operation and return the new
     * operation.
     *
     * @param FieldOperation $previous Previous operation.
     *
     * @throws \Exception
     *
     * @return FieldOperation Merged operation result.
     */
    public function _mergeWithPrevious($previous)
    {
        if ($previous == null) {
            return $this;
        }
        if ($previous instanceof self) {
            if ($previous->targetClassName != null
                && $previous->targetClassName != $this->targetClassName
            ) {
                throw new Exception(
                    'Related object object must be of class '
                    .$this->targetClassName.', but '.$previous->targetClassName
                    .' was passed in.'
                );
            }
            $newRelationToAdd = self::convertToOneDimensionalArray(
                $this->relationsToAdd
            );
            $newRelationToRemove = self::convertToOneDimensionalArray(
                $this->relationsToRemove
            );

            $previous->addObjects(
                $newRelationToAdd,
                $previous->relationsToAdd
            );
            $previous->removeObjects(
                $newRelationToAdd,
                $previous->relationsToRemove
            );

            $previous->removeObjects(
                $newRelationToRemove,
                $previous->relationsToAdd
            );
            $previous->addObjects(
                $newRelationToRemove,
                $previous->relationsToRemove
            );

            $newRelationToAdd = self::convertToOneDimensionalArray(
                $previous->relationsToAdd
            );
            $newRelationToRemove = self::convertToOneDimensionalArray(
                $previous->relationsToRemove
            );

            return new self(
                $newRelationToAdd,
                $newRelationToRemove
            );
        }
        throw new Exception('Operation is invalid after previous operation.');
    }

    /**
     * Returns an associative array encoding of the current operation.
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function _encode()
    {
        $addRelation = [];
        $removeRelation = [];
        if (!empty($this->relationsToAdd)) {
            $addRelation = [
                '__op'    => 'AddRelation',
                'objects' => ParseClient::_encode(
                    self::convertToOneDimensionalArray($this->relationsToAdd),
                    true
                ),
            ];
        }
        if (!empty($this->relationsToRemove)) {
            $removeRelation = [
                '__op'    => 'RemoveRelation',
                'objects' => ParseClient::_encode(
                    self::convertToOneDimensionalArray($this->relationsToRemove),
                    true
                ),
            ];
        }
        if (!empty($addRelation) && !empty($removeRelation)) {
            return [
                '__op' => 'Batch',
                'ops'  => [$addRelation, $removeRelation],
            ];
        }

        return empty($addRelation) ? $removeRelation : $addRelation;
    }

    public function _getTargetClass()
    {
        return $this->targetClassName;
    }

    /**
     * Remove element or array of elements from one dimensional array.
     *
     * @param mixed $elements
     * @param array $array
     */
    public static function removeElementsFromArray($elements, &$array)
    {
        if (!is_array($elements)) {
            $elements = [$elements];
        }
        $length = count($array);
        for ($i = 0; $i < $length; $i++) {
            $exist = false;
            foreach ($elements as $element) {
                if ($array[$i] == $element) {
                    $exist = true;
                    break;
                }
            }
            if ($exist) {
                unset($array[$i]);
            }
        }
        $array = array_values($array);
    }

    /**
     * Convert any array to one dimensional array.
     *
     * @param array $array
     *
     * @return array
     */
    public static function convertToOneDimensionalArray($array)
    {
        $newArray = [];
        if (is_array($array)) {
            foreach ($array as $value) {
                $newArray = array_merge($newArray, self::convertToOneDimensionalArray($value));
            }
        } else {
            $newArray[] = $array;
        }

        return $newArray;
    }
}

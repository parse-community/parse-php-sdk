<?php

namespace Parse\Internal;

use Parse\ParseClient;
use Parse\ParseException;
use Parse\ParseObject;

/**
 * Class RemoveOperation - FieldOperation for removing object(s) from array
 * fields
 *
 * @package  Parse
 * @author   Fosco Marotto <fjm@fb.com>
 */
class RemoveOperation implements FieldOperation
{

  /**
   * @var - Array with objects to remove.
   */
  private $objects;

  /**
   * Creates an RemoveOperation with the provided objects.
   *
   * @param array $objects Objects to remove.
   *
   * @throws ParseException
   */
  public function __construct($objects)
  {
    if (!is_array($objects)) {
      throw new ParseException("RemoveOperation requires an array.");
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
    return array('__op' => 'Remove',
                 'objects' => ParseClient::_encode($this->objects, true));
  }

  /**
   * Takes a previous operation and returns a merged operation to replace it.
   *
   * @param FieldOperation $previous Previous operation.
   *
   * @return FieldOperation Merged operation.
   * @throws ParseException
   */
  public function _mergeWithPrevious($previous)
  {
    if (!$previous) {
      return $this;
    }
    if ($previous instanceof DeleteOperation) {
      return $previous;
    }
    if ($previous instanceof SetOperation) {
      return new SetOperation(
        $this->_apply($previous->getValue(), $this->objects, null)
      );
    }
    if ($previous instanceof RemoveOperation) {
      $oldList = $previous->getValue();
      return new RemoveOperation(
        array_merge((array)$oldList, (array)$this->objects)
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
    if (empty($oldValue)) {
      return array();
    }
    $newValue = array();
    foreach ($oldValue as $oldObject) {
      foreach ($this->objects as $newObject) {
        if ($oldObject instanceof ParseObject) {
          if ($newObject instanceof ParseObject
            && !$oldObject->isDirty()
            && $oldObject->getObjectId() == $newObject->getObjectId()) {
            // found the object, won't add it.
          } else {
            $newValue[] = $oldObject;
          }
        } else {
          if ($oldObject !== $newObject) {
            $newValue[] = $oldObject;
          }
        }
      }
    }
    return $newValue;
  }

}
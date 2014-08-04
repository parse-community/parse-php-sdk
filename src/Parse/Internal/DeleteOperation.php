<?php

namespace Parse\Internal;

/**
 * Class DeleteOperation - FieldOperation to remove a key from an object.
 *
 * @package  Parse
 * @author   Fosco Marotto <fjm@fb.com>
 */
class DeleteOperation implements FieldOperation
{

  /**
   * Returns an associative array encoding of the current operation.
   *
   * @return array Associative array encoding the operation.
   */
  public function _encode()
  {
    return array('__op' => 'Delete');
  }

  /**
   * Applies the current operation and returns the result.
   *
   * @param mixed  $oldValue Value prior to this operation.
   * @param mixed  $object   Unused for this operation type.
   * @param string $key      Key to remove from the target object.
   *
   * @return null
   */
  public function _apply($oldValue, $object, $key)
  {
    return null;
  }

  /**
   * Merge this operation with a previous operation and return the result.
   *
   * @param FieldOperation $previous Previous operation.
   *
   * @return FieldOperation Always returns the current operation.
   */
  public function _mergeWithPrevious($previous)
  {
    return $this;
  }

}
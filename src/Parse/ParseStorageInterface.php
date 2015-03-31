<?php

namespace Parse;

/**
 * ParseStorageInterface - Specifies an interface for implementing persistence.
 *
 * @package  Parse
 * @author   Fosco Marotto <fjm@fb.com>
 */
interface ParseStorageInterface
{

  /**
   * Sets a key-value pair in storage.
   *
   * @param string $key   The key to set
   * @param mixed  $value The value to set
   *
   * @return null
   */
  public function set($key, $value);

  /**
   * Remove a key from storage.
   *
   * @param string $key The key to remove.
   *
   * @return null
   */
  public function remove($key);

  /**
   * Gets the value for a key from storage.
   *
   * @param string $key The key to get the value for
   *
   * @return mixed
   */
  public function get($key);

  /**
   * Clear all the values in storage.
   *
   * @return null
   */
  public function clear();

  /**
   * Save the data, if necessary.  This would be a no-op when using the
   * $_SESSION implementation, but could be used for saving to file or
   * database as an action instead of on every set.
   *
   * @return null
   */
  public function save();

  /**
   * Get all keys in storage.
   *
   * @return array
   */
  public function getKeys();

  /**
   * Get all key-value pairs from storage.
   *
   * @return array
   */
  public function getAll();

} 
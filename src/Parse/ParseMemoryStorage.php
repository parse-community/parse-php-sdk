<?php
/**
 * Class ParseMemoryStorage | Parse/ParseMemoryStorage.php
 */

namespace Parse;

/**
 * Class ParseMemoryStorage - Uses non-persisted memory for storage.
 * This is used by default if a PHP Session is not active.
 *
 * @author Fosco Marotto <fjm@fb.com>
 * @package Parse
 */
class ParseMemoryStorage implements ParseStorageInterface
{
    /**
     * Memory storage
     *
     * @var array
     */
    private $storage = [];

    /**
     * Sets a key-value pair in storage.
     *
     * @param string $key   The key to set
     * @param mixed  $value The value to set
     *
     * @return void
     */
    public function set($key, $value)
    {
        $this->storage[$key] = $value;
    }

    /**
     * Remove a key from storage.
     *
     * @param string $key The key to remove.
     *
     * @return void
     */
    public function remove($key)
    {
        unset($this->storage[$key]);
    }

    /**
     * Gets the value for a key from storage.
     *
     * @param string $key The key to get the value for
     *
     * @return mixed
     */
    public function get($key)
    {
        if (isset($this->storage[$key])) {
            return $this->storage[$key];
        }

        return;
    }

    /**
     * Clear all the values in storage.
     *
     * @return null
     */
    public function clear()
    {
        $this->storage = [];
    }

    /**
     * Save the data, if necessary. Not implemented.
     */
    public function save()
    {
        // No action required.
        return;
    }

    /**
     * Get all keys in storage.
     *
     * @return array
     */
    public function getKeys()
    {
        return array_keys($this->storage);
    }

    /**
     * Get all key-value pairs from storage.
     *
     * @return array
     */
    public function getAll()
    {
        return $this->storage;
    }
}

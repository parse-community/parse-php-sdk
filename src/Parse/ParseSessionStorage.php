<?php
/**
 * Class ParseSessionStorage | Parse/ParseSessionStorage.php
 */

namespace Parse;

/**
 * Class ParseSessionStorage - Uses PHP session support for persistent storage.
 *
 * @author Fosco Marotto <fjm@fb.com>
 * @package Parse
 */
class ParseSessionStorage implements ParseStorageInterface
{
    /**
     * Parse will store its values in a specific key.
     *
     * @var string
     */
    private $storageKey = 'parseData';

    /**
     * ParseSessionStorage constructor.
     * @throws ParseException
     */
    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new ParseException(
                'PHP session_start() must be called first.'
            );
        }
        if (!isset($_SESSION[$this->storageKey])) {
            $_SESSION[$this->storageKey] = [];
        }
    }

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
        $_SESSION[$this->storageKey][$key] = $value;
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
        unset($_SESSION[$this->storageKey][$key]);
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
        if (isset($_SESSION[$this->storageKey][$key])) {
            return $_SESSION[$this->storageKey][$key];
        }

        return null;
    }

    /**
     * Clear all the values in storage.
     *
     * @return void
     */
    public function clear()
    {
        $_SESSION[$this->storageKey] = [];
    }

    /**
     * Save the data, if necessary. Not implemented.
     */
    public function save()
    {
        // No action required.    PHP handles persistence for $_SESSION.
        return;
    }

    /**
     * Get all keys in storage.
     *
     * @return array
     */
    public function getKeys()
    {
        return array_keys($_SESSION[$this->storageKey]);
    }

    /**
     * Get all key-value pairs from storage.
     *
     * @return array
     */
    public function getAll()
    {
        return $_SESSION[$this->storageKey];
    }
}

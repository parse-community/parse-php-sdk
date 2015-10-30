<?php

namespace Parse;

/**
 * ParseMemoryStorage - Uses non-persisted memory for storage.
 * This is used by default if a PHP Session is not active.
 *
 * @author Fosco Marotto <fjm@fb.com>
 */
class ParseMemoryStorage implements ParseStorageInterface
{
    /**
     * @var array
     */
    private $storage = [];

    public function set($key, $value)
    {
        $this->storage[$key] = $value;
    }

    public function remove($key)
    {
        unset($this->storage[$key]);
    }

    public function get($key)
    {
        if (isset($this->storage[$key])) {
            return $this->storage[$key];
        }

        return;
    }

    public function clear()
    {
        $this->storage = [];
    }

    public function save()
    {
        // No action required.
        return;
    }

    public function getKeys()
    {
        return array_keys($this->storage);
    }

    public function getAll()
    {
        return $this->storage;
    }
}

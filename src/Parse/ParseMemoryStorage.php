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

    /**
     * {inheritDoc}
     */
    public function set($key, $value)
    {
        $this->storage[$key] = $value;
    }

    /**
     * {inheritDoc}
     */
    public function remove($key)
    {
        unset($this->storage[$key]);
    }

    /**
     * {inheritDoc}
     */
    public function get($key)
    {
        if (isset($this->storage[$key])) {
            return $this->storage[$key];
        }

        return;
    }

    /**
     * {inheritDoc}
     */
    public function clear()
    {
        $this->storage = [];
    }

    /**
     * {inheritDoc}
     */
    public function save()
    {
        // No action required.
        return;
    }

    /**
     * {inheritDoc}
     */
    public function getKeys()
    {
        return array_keys($this->storage);
    }

    /**
     * {inheritDoc}
     */
    public function getAll()
    {
        return $this->storage;
    }
}

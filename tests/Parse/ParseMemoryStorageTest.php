<?php

namespace Parse\Test;

use Parse\ParseClient;
use Parse\ParseMemoryStorage;

use PHPUnit\Framework\TestCase;

class ParseMemoryStorageTest extends TestCase
{
    /**
     * @var ParseMemoryStorage
     */
    private static $parseStorage;

    public static function setUpBeforeClass() : void
    {
        Helper::setUp();
        self::$parseStorage = ParseClient::getStorage();
    }

    public function tearDown() : void
    {
        Helper::tearDown();
        self::$parseStorage->clear();
    }

    public function testIsUsingDefaultStorage()
    {
        $this->assertTrue(
            self::$parseStorage instanceof ParseMemoryStorage
        );
    }

    public function testSetAndGet()
    {
        self::$parseStorage->set('foo', 'bar');
        $this->assertEquals('bar', self::$parseStorage->get('foo'));
    }

    public function testRemove()
    {
        self::$parseStorage->set('foo', 'bar');
        self::$parseStorage->remove('foo');
        $this->assertNull(self::$parseStorage->get('foo'));
    }

    public function testClear()
    {
        self::$parseStorage->set('foo', 'bar');
        self::$parseStorage->set('foo2', 'bar');
        self::$parseStorage->set('foo3', 'bar');
        self::$parseStorage->clear();
        $this->assertEmpty(self::$parseStorage->getKeys());
    }

    public function testGetAll()
    {
        self::$parseStorage->set('foo', 'bar');
        self::$parseStorage->set('foo2', 'bar');
        self::$parseStorage->set('foo3', 'bar');
        $result = self::$parseStorage->getAll();
        $this->assertEquals('bar', $result['foo']);
        $this->assertEquals('bar', $result['foo2']);
        $this->assertEquals('bar', $result['foo3']);
        $this->assertEquals(3, count($result));
    }

    public function testSave()
    {
        // does nothing
        self::$parseStorage->save();
        $this->assertTrue(true);
    }
}

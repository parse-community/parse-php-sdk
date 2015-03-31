<?php

use Parse\ParseClient;
use Parse\ParseSessionStorage;

require_once 'ParseTestHelper.php';

class ParseSessionStorageTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ParseSessionStorage
     */
    private static $parseStorage;

    public static function setUpBeforeClass()
    {
        ParseClient::_unsetStorage();
        session_start();
        ParseTestHelper::setUp();
        self::$parseStorage = ParseClient::getStorage();
    }

    public function tearDown()
    {
        ParseTestHelper::tearDown();
        self::$parseStorage->clear();
    }

    public static function tearDownAfterClass()
    {
        session_destroy();
    }

    public function testIsUsingParseSession()
    {
        $this->assertTrue(self::$parseStorage instanceof Parse\ParseSessionStorage);
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
}

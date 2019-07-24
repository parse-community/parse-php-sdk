<?php

namespace Parse\Test;

use Parse\ParseClient;
use Parse\ParseSessionStorage;

use PHPUnit\Framework\TestCase;

class ParseSessionStorageTest extends TestCase
{
    /**
     * @var ParseSessionStorage
     */
    private static $parseStorage;

    public static function setUpBeforeClass() : void
    {
        ParseClient::_unsetStorage();

        // indicate we should not use cookies
        ini_set("session.use_cookies", 0);
        // indicate we can use something other than cookies
        ini_set("session.use_only_cookies", 0);
        // enable transparent sid support, for url based sessions
        ini_set("session.use_trans_sid", 1);
        // clear cache control for session pages
        ini_set("session.cache_limiter", "");

        session_start();
        Helper::setUp();
        self::$parseStorage = ParseClient::getStorage();
    }

    public function tearDown() : void
    {
        Helper::tearDown();
        self::$parseStorage->clear();
    }

    public static function tearDownAfterClass() : void
    {
        session_destroy();
    }

    public function testIsUsingParseSession()
    {
        $this->assertTrue(self::$parseStorage instanceof ParseSessionStorage);
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

    /**
     * @group session-recreate-storage
     */
    public function testRecreatingSessionStorage()
    {
        unset($_SESSION['parseData']);

        $this->assertFalse(isset($_SESSION['parseData']));

        new ParseSessionStorage();

        $this->assertEmpty($_SESSION['parseData']);
    }
}

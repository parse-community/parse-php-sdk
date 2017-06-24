<?php

namespace Parse\Test;

use Parse\ParseException;
use Parse\ParseHooks;
use Parse\ParseSchema;
use PHPUnit_Framework_TestCase;

class ParseHooksTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ParseHooks
     */
    private static $hooks;

    public static function setUpBeforeClass()
    {
        Helper::setUp();
    }

    public function setUp()
    {
        $createClass = new ParseSchema('Game');
        $createClass->save();
        self::$hooks = new ParseHooks();
    }

    public function tearDown()
    {
        $createClass = new ParseSchema('Game');
        $createClass->delete();
        Helper::tearDown();
    }

    public function testSingleFunction()
    {
        self::$hooks->createFunction('baz', 'https://api.example.com/baz');

        $function = self::$hooks->fetchFunction('baz');
        $this->assertEquals([
            'functionName' => 'baz',
            'url' => 'https://api.example.com/baz'
        ], $function);

        self::$hooks->deleteFunction('baz');
    }

    public function testSingleFunctionNotFound()
    {
        $this->setExpectedException('Parse\ParseException', 'no function named: sendMessage is defined', 143);
        self::$hooks->fetchFunction('sendMessage');
    }

    public function testEmptyFetchTriggers()
    {
        $this->assertEmpty(self::$hooks->fetchTriggers());
    }

    public function testSingleTriggerNotFound()
    {
        $this->setExpectedException('Parse\ParseException', 'class Scores does not exist', 143);
        self::$hooks->fetchTrigger('Scores', 'beforeSave');
    }

    public function testCreateFunction()
    {
        $function = self::$hooks->createFunction('baz', 'https://api.example.com/baz');
        $this->assertEquals([
            'functionName' => 'baz',
            'url' => 'https://api.example.com/baz'
        ], $function);

        self::$hooks->deleteFunction('baz');
    }

    public function testCreateFunctionAlreadyExists()
    {
        self::$hooks->createFunction('baz', 'https://api.example.com/baz');

        try {
            self::$hooks->createFunction('baz', 'https://api.example.com/baz');
        } catch (ParseException $ex) {
            $this->assertEquals(
                'function name: baz already exits',
                $ex->getMessage()
            );
        }

        self::$hooks->deleteFunction('baz');
    }

    /**
     * @group hook-create-trigger
     */
    public function testCreateTrigger()
    {
        $trigger = self::$hooks->createTrigger('Game', 'beforeSave', 'https://api.example.com/Game/beforeSave');
        // validate
        $this->assertEquals([
            'className'   => 'Game',
            'triggerName' => 'beforeSave',
            'url'         => 'https://api.example.com/Game/beforeSave',
        ], $trigger);

        // fetch and revalidate
        $trigger = self::$hooks->fetchTrigger('Game', 'beforeSave');
        $this->assertEquals([
            'className'   => 'Game',
            'triggerName' => 'beforeSave',
            'url'         => 'https://api.example.com/Game/beforeSave',
        ], $trigger);


        self::$hooks->deleteTrigger('Game', 'beforeSave');
    }

    public function testCreateTriggerAlreadyExists()
    {
        self::$hooks->createTrigger('Game', 'beforeDelete', 'https://api.example.com/Game/beforeDelete');

        try {
            self::$hooks->createTrigger('Game', 'beforeDelete', 'https://api.example.com/Game/beforeDelete');
            $this->fail();
        } catch (ParseException $ex) {
            $this->assertEquals(
                'class Game already has trigger beforeDelete',
                $ex->getMessage()
            );
        }

        self::$hooks->deleteTrigger('Game', 'beforeDelete');
    }

    public function testEditFunction()
    {
        self::$hooks->createFunction('baz', 'https://api.example.com/baz');

        $edited_function = self::$hooks->editFunction('baz', 'https://api.example.com/_baz');
        $this->assertEquals([
            'functionName' => 'baz',
            'url' => 'https://api.example.com/_baz'
        ], $edited_function);

        self::$hooks->deleteFunction('baz');
    }

    public function testEditTrigger()
    {
        self::$hooks->createTrigger('Game', 'beforeSave', 'https://api.example.com/Game/beforeSave');

        $edited_trigger = self::$hooks->editTrigger('Game', 'beforeSave', 'https://api.example.com/Game/_beforeSave');
        $this->assertEquals([
            'className'   => 'Game',
            'triggerName' => 'beforeSave',
            'url'         => 'https://api.example.com/Game/_beforeSave',
        ], $edited_trigger);

        self::$hooks->deleteTrigger('Game', 'beforeSave');
    }

    public function testDeleteFunction()
    {
        self::$hooks->createFunction('foo', 'https://api.example.com/foo');

        $deleted_function = self::$hooks->deleteFunction('foo');
        $this->assertEmpty($deleted_function);
    }

    public function testDeleteTrigger()
    {
        self::$hooks->createTrigger('Game', 'beforeSave', 'https://api.example.com/Game/beforeSave');

        $deleted_trigger = self::$hooks->deleteTrigger('Game', 'beforeSave');
        $this->assertEmpty($deleted_trigger);
    }

    /**
     * @group hooks-fetch-functions
     */
    public function testFetchFunctions()
    {
        self::$hooks->createFunction('func1', 'http://example1.com');
        self::$hooks->createFunction('func2', 'http://example2.com');
        self::$hooks->createFunction('func3', 'http://example3.com');

        $functions = self::$hooks->fetchFunctions();

        $this->assertEquals([
            [
                'functionName'  => 'func1',
                'url'           => 'http://example1.com'
            ],
            [
                'functionName'  => 'func2',
                'url'           => 'http://example2.com'
            ],
            [
                'functionName'  => 'func3',
                'url'           => 'http://example3.com'
            ]
        ], $functions);

        self::$hooks->deleteFunction('func1');
        self::$hooks->deleteFunction('func2');
        self::$hooks->deleteFunction('func3');
    }
}

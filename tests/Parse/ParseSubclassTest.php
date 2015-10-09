<?php

namespace Parse\Test;

use Parse\ParseInstallation;
use Parse\ParseObject;

class ParseSubclassTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Helper::setUp();
    }

    public function tearDown()
    {
        Helper::tearDown();
    }

    public function testCreateFromSubclass()
    {
        $install = new ParseInstallation();
        $this->assertTrue($install instanceof ParseInstallation);
        $this->assertTrue(is_subclass_of($install, 'Parse\ParseObject'));
    }

    public function testCreateFromParseObject()
    {
        $install = ParseObject::create('_Installation');
        $this->assertTrue($install instanceof ParseInstallation);
        $this->assertTrue(is_subclass_of($install, 'Parse\ParseObject'));
    }
}

<?php

namespace Parse\Test;

use Parse\ParseInstallation;
use Parse\ParseObject;

use PHPUnit\Framework\TestCase;

class ParseSubclassTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        Helper::setUp();
    }

    public function tearDown() : void
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

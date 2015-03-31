<?php

use Parse\ParseConfig;

require_once 'ParseTestHelper.php';

class ParseConfigMock extends ParseConfig
{
    public function __construct()
    {
        $this->setConfig(["foo" => "bar", "some" => 1]);
    }
}

class ParseConfigTest extends PHPUnit_Framework_TestCase
{
    public function testGetConfig()
    {
        $config = new ParseConfigMock();
        $this->assertEquals("bar", $config->get("foo"));
        $this->assertEquals(1, $config->get("some"));
    }
}

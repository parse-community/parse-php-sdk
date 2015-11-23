<?php

namespace Parse\Test;

class ParseConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfig()
    {
        $config = new ConfigMock();
        $this->assertEquals('bar', $config->get('foo'));
        $this->assertEquals(1, $config->get('some'));
    }
}

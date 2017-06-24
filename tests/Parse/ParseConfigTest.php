<?php

namespace Parse\Test;

use Parse\ParseConfig;

class ParseConfigTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Helper::setUp();
    }

    /**
     * @group parse-config
     */
    public function testDefaultConfig()
    {
        $config = new ParseConfig();
        $this->assertEquals([], $config->getConfig());
    }

    /**
     * @group parse-config
     */
    public function testGetConfig()
    {
        $config = new ConfigMock();
        $this->assertEquals('bar', $config->get('foo'));
        $this->assertEquals(1, $config->get('some'));

        // check null value
        $this->assertNull($config->get('notakey'));

        // check html value
        $this->assertEquals('<value>', $config->get('another'));
    }

    /**
     * @group parse-config
     */
    public function testEscapeConfig()
    {
        $config = new ConfigMock();

        // check html encoded value
        $this->assertEquals('&lt;value&gt;', $config->escape('another'));

        // check null value
        $this->assertNull($config->escape('notakey'));

        // check normal value
        $this->assertEquals('bar', $config->escape('foo'));
    }
}

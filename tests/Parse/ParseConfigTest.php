<?php

namespace Parse\Test;

use Parse\ParseConfig;

use PHPUnit\Framework\TestCase;

class ParseConfigTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        Helper::setUp();
    }

    public function tearDown() : void
    {
        // clear config on tear down
        Helper::clearClass('_GlobalConfig');
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
        $this->assertEquals('<html>value</html>', $config->get('another'));
    }

    /**
     * @group parse-config
     */
    public function testEscapeConfig()
    {
        $config = new ConfigMock();

        // check html encoded value
        $this->assertEquals('&lt;html&gt;value&lt;/html&gt;', $config->escape('another'));

        // check null value
        $this->assertNull($config->escape('notakey'));

        // check normal value
        $this->assertEquals('bar', $config->escape('foo'));
    }

    /**
     * @group parse-config
     */
    public function testSaveConfig()
    {
        $config = new ParseConfig();
        $this->assertNull($config->get('key'));
        $config->set('key', 'value');
        $config->save();

        $config = new ParseConfig();
        $this->assertEquals($config->get('key'), 'value');
    }
}

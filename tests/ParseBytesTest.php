<?php

use Parse\ParseBytes;
use Parse\ParseObject;
use Parse\ParseQuery;

require_once 'ParseTestHelper.php';

class ParseBytesTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        ParseTestHelper::setUp();
    }

    public function setUp()
    {
        ParseTestHelper::clearClass("BytesObject");
    }

    public function tearDown()
    {
        ParseTestHelper::clearClass("BytesObject");
        ParseTestHelper::tearDown();
    }

    public function testParseBytesFromArray()
    {
        $obj = ParseObject::create("BytesObject");
        $bytes = ParseBytes::createFromByteArray([70, 111, 115, 99, 111]);
        $obj->set("byteColumn", $bytes);
        $obj->save();

        $query = new ParseQuery("BytesObject");
        $objAgain = $query->get($obj->getObjectId());
        $this->assertEquals("Fosco", $objAgain->get("byteColumn"));
    }

    public function testParseBytesFromBase64Data()
    {
        $obj = ParseObject::create("BytesObject");
        $bytes = ParseBytes::createFromBase64Data("R3JhbnRsYW5k");
        $obj->set("byteColumn", $bytes);
        $obj->save();

        $query = new ParseQuery("BytesObject");
        $objAgain = $query->get($obj->getObjectId());
        $this->assertEquals("Grantland", $objAgain->get("byteColumn"));
    }
}

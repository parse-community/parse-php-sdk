<?php

namespace Parse\Test;

use Parse\ParseBytes;
use Parse\ParseObject;
use Parse\ParseQuery;

use PHPUnit\Framework\TestCase;

class ParseBytesTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        Helper::setUp();
    }

    public function setup() : void
    {
        Helper::clearClass('BytesObject');
    }

    public function tearDown() : void
    {
        Helper::clearClass('BytesObject');
        Helper::tearDown();
    }

    public function testParseBytesFromArray()
    {
        $obj = ParseObject::create('BytesObject');
        $bytes = ParseBytes::createFromByteArray([70, 111, 115, 99, 111]);
        $obj->set('byteColumn', $bytes);
        $obj->save();

        $query = new ParseQuery('BytesObject');
        $objAgain = $query->get($obj->getObjectId());
        $this->assertEquals('Fosco', $objAgain->get('byteColumn'));
    }

    public function testParseBytesFromBase64Data()
    {
        $obj = ParseObject::create('BytesObject');
        $bytes = ParseBytes::createFromBase64Data('R3JhbnRsYW5k');
        $obj->set('byteColumn', $bytes);
        $obj->save();

        $query = new ParseQuery('BytesObject');
        $objAgain = $query->get($obj->getObjectId());
        $this->assertEquals('Grantland', $objAgain->get('byteColumn'));
    }
}

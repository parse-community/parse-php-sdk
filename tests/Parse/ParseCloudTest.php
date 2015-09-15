<?php

namespace Parse\Test;

use Parse\ParseCloud;
use Parse\ParseGeoPoint;
use Parse\ParseObject;

class ParseCloudTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Helper::setUp();
    }

    public function testFunctionsWithObjectParamsFails()
    {
        $obj = ParseObject::create('SomeClass');
        $obj->set('name', 'Zanzibar');
        $obj->save();
        $params = ['key1' => $obj];
        $this->setExpectedException('\Exception', 'ParseObjects not allowed');
        ParseCloud::run('foo', $params);
    }

    public function testFunctionsWithGeoPointParamsDoNotThrow()
    {
        $params = ['key1' => new ParseGeoPoint(50, 50)];
        $this->setExpectedException('Parse\ParseException', 'function not found');
        ParseCloud::run('unknown_function', $params);
    }

    public function testUnknownFunctionFailure()
    {
        $params = ['key1' => 'value1'];
        $this->setExpectedException('Parse\ParseException', 'function not found');
        ParseCloud::run('unknown_function', $params);
    }
}

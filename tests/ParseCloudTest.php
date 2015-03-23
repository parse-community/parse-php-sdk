<?php

use Parse\ParseCloud;
use Parse\ParseObject;
use Parse\ParseGeoPoint;

require_once 'ParseTestHelper.php';

class ParseCloudTest extends PHPUnit_Framework_TestCase
{

  public static function setUpBeforeClass()
  {
    ParseTestHelper::setUp();
  }

  public function testFunctionsWithObjectParamsFails()
  {
    $obj = ParseObject::create('SomeClass');
    $obj->set('name', 'Zanzibar');
    $obj->save();
    $params = array('key1' => $obj);
    $this->setExpectedException('\Exception', 'ParseObjects not allowed');
    ParseCloud::run('foo', $params);
  }

  public function testFunctionsWithGeoPointParamsDoNotThrow()
  {
    $params = array('key1' => new ParseGeoPoint(50, 50));
    $this->setExpectedException('Parse\ParseException', 'function not found');
    ParseCloud::run('unknown_function', $params);
  }

  public function testUnknownFunctionFailure()
  {
    $params = array('key1' => 'value1');
    $this->setExpectedException('Parse\ParseException','function not found');
    ParseCloud::run('unknown_function', $params);
  }

}
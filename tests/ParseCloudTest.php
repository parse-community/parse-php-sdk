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

  public function testExplicitFunctionFailure()
  {
    $params = array('key1' => 'value1');
    $this->setExpectedException('Parse\ParseException','bad stuff happened');
    ParseCloud::run('bar', $params);
  }

  public function testUnknownFunctionFailure()
  {
    $params = array('key1' => 'value1');
    $this->setExpectedException('Parse\ParseException','function not found');
    ParseCloud::run('unknown_function', $params);
  }

  public function testFunctions()
  {
    $params = array(
      'key1' => 'value1',
      'key2' => array(1,2,3)
    );
    $response = ParseCloud::run('foo', $params);
    $obj = $response['object'];
    $this->assertTrue($obj instanceof ParseObject);
    $this->assertEquals('Foo', $obj->className);
    $this->assertEquals(2, $obj->get('x'));
    $relation = $obj->get('relation');
    $this->assertTrue($relation instanceof ParseObject);
    $this->assertEquals('Bar', $relation->className);
    $this->assertEquals(3, $relation->get('x'));
    $obj = $response['array'][0];
    $this->assertTrue($obj instanceof ParseObject);
    $this->assertEquals('Bar', $obj->className);
    $this->assertEquals(2, $obj->get('x'));

    $response = ParseCloud::run('foo', array('key1' => 'value1'));
    $this->assertEquals(2, $response['a']);

    try {
      $response = ParseCloud::run('bar', array('key1' => 'value1'));
      $this->fail('Should have thrown an exception.');
    } catch(Parse\ParseException $ex) {
      // A parse exception should occur.
    }

    $response = ParseCloud::run('bar', array('key2' => 'value1'));
    $this->assertEquals('Foo', $response);

    $obj = ParseObject::create('SomeClass');
    $obj->set('name', 'Zanzibar');
    $obj->save();

    $params = array('key2' => 'value1', 'key1' => $obj);
    try {
      $response = ParseCloud::run('foo', $params);
      $this->fail('Should have thrown an exception.');
    } catch (\Exception $ex) {
      // An exception should occur.
    }
  }
}
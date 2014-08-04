<?php

use Parse\ParseQuery;
use Parse\ParsePush;
use Parse\ParseInstallation;

require_once 'ParseTestHelper.php';

class ParsePushTest extends PHPUnit_Framework_TestCase
{

  public static function setUpBeforeClass()
  {
    ParseTestHelper::setUp();
  }

  public function tearDown()
  {
    ParseTestHelper::tearDown();
  }

  public function testBasicPush()
  {
    ParsePush::send(array(
      'channels' => array(''),
      'data' => array('alert' => 'sample message')
    ));
  }

  public function testPushToQuery()
  {
    $query = ParseInstallation::query();
    $query->equalTo('key', 'value');
    ParsePush::send(array(
      'data' => array('alert' => 'iPhone 5 is out!'),
      'where' => $query
    ));
  }

  public function testPushDates()
  {
    ParsePush::send(array(
      'data' => array('alert' => 'iPhone 5 is out!'),
      'push_time' => new DateTime(),
      'expiration_time' => new DateTime(),
      'channels' => array()
    ));
  }
}
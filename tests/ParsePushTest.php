<?php

use Parse\ParseInstallation;
use Parse\ParsePush;

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
        ParsePush::send(
            [
            'channels' => [''],
            'data'     => ['alert' => 'sample message'],
            ]
        );
    }

    public function testPushToQuery()
    {
        $query = ParseInstallation::query();
        $query->equalTo('key', 'value');
        ParsePush::send(
            [
            'data'  => ['alert' => 'iPhone 5 is out!'],
            'where' => $query,
            ]
        );
    }

    public function testPushDates()
    {
        ParsePush::send(
            [
            'data'            => ['alert' => 'iPhone 5 is out!'],
            'push_time'       => new DateTime(),
            'expiration_time' => new DateTime(),
            'channels'        => [],
            ]
        );
    }
}

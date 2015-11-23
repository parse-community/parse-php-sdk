<?php

namespace Parse\Test;

use Parse\ParseInstallation;
use Parse\ParsePush;

class ParsePushTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Helper::setUp();
    }

    public function tearDown()
    {
        Helper::tearDown();
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
            'push_time'       => new \DateTime(),
            'expiration_time' => new \DateTime(),
            'channels'        => [],
            ]
        );
    }
}

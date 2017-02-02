<?php

namespace Parse\Test;

use Parse\ParseException;
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

    public function testNoMasterKey() {
        $this->setExpectedException(ParseException::class);

        ParsePush::send(
            [
                'channels' => [''],
                'data'     => ['alert' => 'sample message'],
            ]
        );
    }

    public function testBasicPush()
    {
        ParsePush::send(
            [
            'channels' => [''],
            'data'     => ['alert' => 'sample message'],
            ]
        , true);
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
        , true);

    }

    public function testPushToQueryWithoutWhere()
    {
        $query = ParseInstallation::query();
        ParsePush::send(
            [
                'data'  => ['alert' => 'Done without conditions!'],
                'where' => $query,
            ]
            , true);

    }

    public function testNonQueryWhere()
    {
        $this->setExpectedException(\Exception::class,
            'Where parameter for Parse Push must be of type ParseQuery');
        ParsePush::send(
            [
                'data'  => ['alert' => 'Will this really work?'],
                'where' => 'not-a-query',
            ]
            , true);

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
        , true);
    }

    public function testExpirationTimeAndIntervalSet()
    {
        $this->setExpectedException(\Exception::class,
            'Both expiration_time and expiration_interval can\'t be set.');
        ParsePush::send(
            [
                'data'            => ['alert' => 'iPhone 5 is out!'],
                'push_time'       => new \DateTime(),
                'expiration_time' => new \DateTime(),
                'expiration_interval'   => 90,
                'channels'        => [],
            ]
            , true);

    }

    public function testPushHasHeaders()
    {
        $response = ParsePush::send(
            [
                'channels' => [''],
                'data'     => ['alert' => 'sample message'],
            ]
        , true);

        $this->assertArrayHasKey('_headers', $response);
    }
}

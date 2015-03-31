<?php

use Parse\ParseAnalytics;

require_once 'ParseTestHelper.php';

class ParseAnalyticsTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        ParseTestHelper::setUp();
    }

    public function tearDown()
    {
        ParseTestHelper::tearDown();
    }

    public function assertAnalyticsValidation($event, $params, $expectedJSON)
    {
        // We'll test that the event encodes properly, and that the analytics call
        // doesn't throw an exception.
        $json = ParseAnalytics::_toSaveJSON($params ?: []);
        $this->assertEquals($expectedJSON, $json);
        ParseAnalytics::track($event, $params ?: []);
    }

    public function testTrackEvent()
    {
        $expected = '{"dimensions":{}}';
        $this->assertAnalyticsValidation('testTrackEvent', null, $expected);
    }

    public function testFailsOnEventName1()
    {
        $this->setExpectedException(
            'Exception', 'A name for the custom event must be provided.'
        );
        ParseAnalytics::track('');
    }

    public function testFailsOnEventName2()
    {
        $this->setExpectedException(
            'Exception', 'A name for the custom event must be provided.'
        );
        ParseAnalytics::track('    ');
    }

    public function testFailsOnEventName3()
    {
        $this->setExpectedException(
            'Exception', 'A name for the custom event must be provided.'
        );
        ParseAnalytics::track("    \n");
    }

    public function testTrackEventDimensions()
    {
        $expected = '{"dimensions":{"foo":"bar","bar":"baz"}}';
        $params = [
            'foo' => 'bar',
            'bar' => 'baz',
        ];
        $this->assertAnalyticsValidation('testDimensions', $params, $expected);

        $date = date(DATE_RFC3339);
        $expected = '{"dimensions":{"foo":"bar","bar":"baz","someDate":"'.
            $date.'"}}';
        $params = [
            'foo'      => 'bar',
            'bar'      => 'baz',
            'someDate' => $date,
        ];
        $this->assertAnalyticsValidation('testDate', $params, $expected);
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: Bfriedman
 * Date: 11/7/17
 * Time: 12:40
 */

namespace Parse\Test;

use Parse\ParseException;
use Parse\ParseLogs;
use Parse\ParseObject;
use Parse\ParseUser;

class ParseLogsTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        // setup 15 log entries that we can reference
        $objs = [];
        while (count($objs) < 15) {
            $obj = new ParseObject('TestObject');
            $objs[] = $obj;
        }
        ParseObject::saveAll($objs);
    }

    public static function tearDownAfterClass()
    {
        Helper::clearClass('TestObject');
    }

    /**
     * @group parse-logs-tests
     */
    public function testGettingDefaultLogs()
    {
        $logs = ParseLogs::getScriptLogs('info', 1);
        $this->assertNotEmpty($logs);
        $this->assertEquals(1, count($logs));
    }

    /**
     * @group parse-logs-tests
     */
    public function testGettingOneLog()
    {
        $logs = ParseLogs::getInfoLogs(1);
        $this->assertEquals(1, count($logs));
        $this->assertEquals($logs[0]['method'], 'GET');
        $this->assertTrue(isset($logs[0]['url']));
    }

    /**
     * @group parse-logs-tests
     */
    public function testFrom()
    {
        // test getting logs from 4 hours in the future
        $date = new \DateTime();
        $date->add(new \DateInterval('PT4H'));
        $logs = ParseLogs::getInfoLogs(1, $date);
        $this->assertEquals(0, count($logs));
    }

    /**
     * @group parse-logs-tests
     */
    public function testUntil()
    {
        // test getting logs from 1950 years in the past (not likely...)
        $date = new \DateTime();
        $date->sub(new \DateInterval('P1950Y'));
        $logs = ParseLogs::getInfoLogs(1, null, $date);
        $this->assertEquals(0, count($logs));
    }

    /**
     * @group parse-logs-tests
     */
    public function testOrderAscending()
    {
        $logs = ParseLogs::getInfoLogs(15, null, null, 'asc');
        $this->assertEquals(15, count($logs));

        $timestamp1 = $logs[0]['timestamp'];
        $timestamp2 = $logs[count($logs)-1]['timestamp'];

        $timestamp1 = preg_replace('/Z$/', '', $timestamp1);
        $timestamp2 = preg_replace('/Z$/', '', $timestamp2);

        // get first 2 entries
        $entryDate1 = \DateTime::createFromFormat('Y-m-d\TH:i:s.u', $timestamp1);
        $entryDate2 = \DateTime::createFromFormat('Y-m-d\TH:i:s.u', $timestamp2);

        $this->assertTrue($entryDate1 < $entryDate2);
    }
}

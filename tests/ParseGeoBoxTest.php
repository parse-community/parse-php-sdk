<?php

use Parse\ParseException;
use Parse\ParseGeoPoint;
use Parse\ParseObject;
use Parse\ParseQuery;

require_once 'ParseTestHelper.php';

class ParseGeoBoxTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        ParseTestHelper::setUp();
    }

    public function setUp()
    {
        ParseTestHelper::clearClass("TestObject");
    }

    public function tearDown()
    {
        ParseTestHelper::tearDown();
    }

    public function testGeoBox()
    {
        $caltrainStationLocation = new ParseGeoPoint(37.776346, -122.394218);
        $caltrainStation = ParseObject::create('TestObject');
        $caltrainStation->set('location', $caltrainStationLocation);
        $caltrainStation->set('name', 'caltrain');
        $caltrainStation->save();

        $santaClaraLocation = new ParseGeoPoint(37.325635, -121.945753);
        $santaClara = new ParseObject('TestObject');

        $santaClara->set('location', $santaClaraLocation);
        $santaClara->set('name', 'santa clara');
        $santaClara->save();

        $southwestOfSF = new ParseGeoPoint(37.708813, -122.526398);
        $northeastOfSF = new ParseGeoPoint(37.822802, -122.373962);

        // Try a correct query
        $query = new ParseQuery('TestObject');
        $query->withinGeoBox('location', $southwestOfSF, $northeastOfSF);
        $objectsInSF = $query->find();
        $this->assertEquals(1, count($objectsInSF));
        $this->assertEquals('caltrain', $objectsInSF[0]->get('name'));

        // Switch order of args, should fail because it crosses the dateline
        $query = new ParseQuery('TestObject');
        $query->withinGeoBox('location', $northeastOfSF, $southwestOfSF);
        try {
            $results = $query->find();
            $this->assertTrue(false, 'Query should fail because it crosses dateline');
        } catch (ParseException $e) {
        }

        $northwestOfSF = new ParseGeoPoint(37.822802, -122.526398);
        $southeastOfSF = new ParseGeoPoint(37.708813, -122.373962);

        // Switch just longitude, should fail because it crosses the dateline
        $query = new ParseQuery('TestObject');
        $query->withinGeoBox('location', $southeastOfSF, $northwestOfSF);
        try {
            $query->find();
            $this->assertTrue(false, 'Query should fail because it crosses dateline');
        } catch (ParseException $e) {
        }

        // Switch just the latitude, should fail because it doesnt make sense
        $query = new ParseQuery('TestObject');
        $query->withinGeoBox('location', $northwestOfSF, $southeastOfSF);
        try {
            $query->find();
            $this->assertTrue(false, 'Query should fail because it makes no sense');
        } catch (ParseException $e) {
        }
    }

    public function testGeoBoxSmallNearDateLine()
    {
        $nearWestOfDateLine = new ParseGeoPoint(0, 175);
        $nearWestObject = ParseObject::create('TestObject');

        $nearWestObject->set('location', $nearWestOfDateLine);
        $nearWestObject->set('name', 'near west');
        $nearWestObject->set('order', 1);
        $nearWestObject->save();

        $nearEastOfDateLine = new ParseGeoPoint(0, -175);
        $nearEastObject = ParseObject::create('TestObject');

        $nearEastObject->set('location', $nearEastOfDateLine);
        $nearEastObject->set('name', 'near east');
        $nearEastObject->set('order', 2);
        $nearEastObject->save();

        $farWestOfDateLine = new ParseGeoPoint(0, 165);
        $farWestObject = ParseObject::create('TestObject');

        $farWestObject->set('location', $farWestOfDateLine);
        $farWestObject->set('name', 'far west');
        $farWestObject->set('order', 3);
        $farWestObject->save();

        $farEastOfDateLine = new ParseGeoPoint(0, -165);
        $farEastObject = ParseObject::create('TestObject');

        $farEastObject->set('location', $farEastOfDateLine);
        $farEastObject->set('name', 'far east');
        $farEastObject->set('order', 4);
        $farEastObject->save();

        $southwestOfDateLine = new ParseGeoPoint(-10, 170);
        $northeastOfDateLine = new ParseGeoPoint(10, -170);

        $query = new ParseQuery('TestObject');
        $query->withinGeoBox('location', $southwestOfDateLine, $northeastOfDateLine);
        $query->ascending('order');
        try {
            $query->find();
            $this->assertTrue(false, 'Query should fail for crossing the date line.');
        } catch (ParseException $e) {
        }
    }

    public function testGeoBoxTooLarge()
    {
        $centerPoint = new ParseGeoPoint(0, 0);
        $center = ParseObject::create('TestObject');

        $center->set('location', $centerPoint);
        $center->save();

        $southwest = new ParseGeoPoint(-89, -179);
        $northeast = new ParseGeoPoint(89, 179);

        // This is an interesting test case because mongo can actually handle this
        // kind of query, but
        // if one actually happens, it's probably that the developer switches the
        // two points.
        $query = new ParseQuery('TestObject');
        $query->withinGeoBox('location', $southwest, $northeast);
        try {
            $query->find();
            $this->assertTrue(false, 'Query should fail for being too large.');
        } catch (ParseException $e) {
        }
    }
}

<?php

namespace Parse\Test;

use Parse\ParseGeoPoint;
use Parse\ParseObject;
use Parse\ParseQuery;

use PHPUnit\Framework\TestCase;

class ParseGeoBoxTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        Helper::setUp();
    }

    public function setup() : void
    {
        Helper::clearClass('TestObject');
    }

    public function tearDown() : void
    {
        Helper::tearDown();
    }

    /**
     * @group test-geo-box
     */
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

        // Switch order of args
        // (note) used to fail on old parse, passes in the open source variant
        $query = new ParseQuery('TestObject');
        $query->withinGeoBox('location', $northeastOfSF, $southwestOfSF);
        $objectsInSF = $query->find();
        $this->assertEquals(1, count($objectsInSF));
        $this->assertEquals('caltrain', $objectsInSF[0]->get('name'));
        // TODO remove
        /* , should fail because it crosses the dateline
        try {
            $results = $query->find();
            $this->assertTrue(false, 'Query should fail because it crosses
            dateline with results:'.json_encode($results[0]));
        } catch (ParseException $e) {
        }
        */

        $northwestOfSF = new ParseGeoPoint(37.822802, -122.526398);
        $southeastOfSF = new ParseGeoPoint(37.708813, -122.373962);

        // Switch just longitude
        // (note) used to fail on old parse, passes in the open source variant
        $query = new ParseQuery('TestObject');
        $query->withinGeoBox('location', $southeastOfSF, $northwestOfSF);
        $objectsInSF = $query->find();
        $this->assertEquals(1, count($objectsInSF));
        $this->assertEquals('caltrain', $objectsInSF[0]->get('name'));
        // TODO remove
        /* , should fail because it crosses the dateline
        try {
            $query->find();
            $this->assertTrue(false, 'Query should fail because it crosses dateline');
        } catch (ParseException $e) {
        }
        */

        // Switch just the latitude
        // (note) used to fail on old parse, passes in the open source variant
        $query = new ParseQuery('TestObject');
        $query->withinGeoBox('location', $northwestOfSF, $southeastOfSF);
        $objectsInSF = $query->find();
        $this->assertEquals(1, count($objectsInSF));
        $this->assertEquals('caltrain', $objectsInSF[0]->get('name'));
        // TODO remove
        /* , should fail because it doesnt make sense
        try {
            $query->find();
            $this->assertTrue(false, 'Query should fail because it makes no sense');
        } catch (ParseException $e) {
        }
        */
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

        // (note) used to fail on old parse, passes in the open source variant
        $query = new ParseQuery('TestObject');
        $query->withinGeoBox('location', $southwestOfDateLine, $northeastOfDateLine);
        $query->ascending('order');
        $objects = $query->find();

        // verify # of objects
        $this->assertCount(2, $objects);

        // verify order of objects
        $this->assertEquals('far west', $objects[0]->get('name'));
        $this->assertEquals('far east', $objects[1]->get('name'));
        /* TODO REMOVE
        try {
            $query->find();
            $this->assertTrue(false, 'Query should fail for crossing the date line.');
        } catch (ParseException $e) {
        }
        */
    }

    public function testGeoBoxTooLarge()
    {
        $centerPoint = new ParseGeoPoint(0, 0);
        $center = ParseObject::create('TestObject');

        $center->set('location', $centerPoint);
        $center->set('name', 'center');
        $center->save();

        $southwest = new ParseGeoPoint(-89, -179);
        $northeast = new ParseGeoPoint(89, 179);

        // This is an interesting test case because mongo can actually handle this
        // kind of query, but
        // if one actually happens, it's probably that the developer switches the
        // two points.
        $query = new ParseQuery('TestObject');
        $query->withinGeoBox('location', $southwest, $northeast);
        $points = $query->find();
        $this->assertCount(1, $points);
        $this->assertEquals('center', $points[0]->get('name'));
        /* TODO REMOVE
        try {
            $query->find();
            $this->assertTrue(false, 'Query should fail for being too large.');
        } catch (ParseException $e) {
        }
        */
    }
}

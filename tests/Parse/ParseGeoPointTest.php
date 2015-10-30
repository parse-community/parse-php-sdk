<?php

namespace Parse\Test;

use Parse\ParseGeoPoint;
use Parse\ParseObject;
use Parse\ParseQuery;

class ParseGeoPointTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Helper::setUp();
    }

    public function setUp()
    {
        Helper::clearClass('TestObject');
    }

    public function tearDown()
    {
        Helper::tearDown();
    }

    public function testGeoPointBase()
    {
        $point = new ParseGeoPoint(44.0, -11.0);
        $obj = ParseObject::create('TestObject');
        $obj->set('location', $point);

        $obj->set('name', 'Ferndale');
        $obj->save();

        // Non geo query
        $query = new ParseQuery('TestObject');
        $query->equalTo('name', 'Ferndale');
        $results = $query->find();
        $this->assertEquals(1, count($results));

        // Round trip encoding
        $actualPoint = $results[0]->get('location');
        $this->assertEquals(44.0, $actualPoint->getLatitude(), '', 0.0001);
        $this->assertEquals(-11.0, $actualPoint->getLongitude(), '', 0.0001);

        // nearsphere
        $point->setLatitude(66.0);
        $query = new ParseQuery('TestObject');
        $query->near('location', $point);
        $results = $query->find();
        $this->assertEquals(1, count($results));
    }

    public function testGeoLine()
    {
        for ($i = 0; $i < 10; ++$i) {
            $obj = ParseObject::create('TestObject');
            $point = new ParseGeoPoint($i * 4.0 - 12.0, $i * 3.2 - 11.0);
            $obj->set('location', $point);
            $obj->set('construct', 'line');
            $obj->set('seq', $i);
            $obj->save();
        }

        $query = new ParseQuery('TestObject');
        $point = new ParseGeoPoint(24.0, 19.0);
        $query->equalTo('construct', 'line');
        $query->withinMiles('location', $point, 10000);
        $results = $query->find();
        $this->assertEquals(10, count($results));
        $this->assertEquals(9, $results[0]->get('seq'));
        $this->assertEquals(6, $results[3]->get('seq'));
    }

    public function testGeoMaxDistance()
    {
        for ($i = 0; $i < 3; ++$i) {
            $obj = ParseObject::create('TestObject');
            $point = new ParseGeoPoint(0.0, $i * 45.0);
            $obj->set('location', $point);
            $obj->set('id', $i);
            $obj->save();
        }

        // baseline all
        $query = new ParseQuery('TestObject');
        $point = new ParseGeoPoint(1.0, -1.0);
        $query->near('location', $point);
        $results = $query->find();
        $this->assertEquals(3, count($results));

        // all
        $query = new ParseQuery('TestObject');
        $query->withinRadians('location', $point, 3.14 * 2);
        $results = $query->find();
        $this->assertEquals(3, count($results));

        // all
        $query = new ParseQuery('TestObject');
        $query->withinRadians('location', $point, 3.14);
        $results = $query->find();
        $this->assertEquals(3, count($results));

        // 2
        $query = new ParseQuery('TestObject');
        $query->withinRadians('location', $point, 3.14 * 0.5);
        $results = $query->find();
        $this->assertEquals(2, count($results));
        $this->assertEquals(1, $results[1]->get('id'));

        // 1
        $query = new ParseQuery('TestObject');
        $query->withinRadians('location', $point, 3.14 * 0.25);
        $results = $query->find();
        $this->assertEquals(1, count($results));
        $this->assertEquals(0, $results[0]->get('id'));
    }

    public function testGeoMaxDistanceWithUnits()
    {
        Helper::clearClass('PlaceObject');
        // [SAC] 38.52 -121.50 Sacramento,CA
        $sacramento = new ParseGeoPoint(38.52, -121.50);
        $obj = ParseObject::create('PlaceObject');
        $obj->set('location', $sacramento);
        $obj->set('name', 'Sacramento');
        $obj->save();

        // [HNL] 21.35 -157.93 Honolulu Int,HI
        $honolulu = new ParseGeoPoint(21.35, -157.93);
        $obj = ParseObject::create('PlaceObject');
        $obj->set('location', $honolulu);
        $obj->set('name', 'Honolulu');
        $obj->save();

        // [51Q] 37.75 -122.68 San Francisco,CA
        $sanfran = new ParseGeoPoint(37.75, -122.68);
        $obj = ParseObject::create('PlaceObject');
        $obj->set('location', $sanfran);
        $obj->set('name', 'San Francisco');
        $obj->save();

        // test point SFO
        $point = new ParseGeoPoint(37.6189722, -122.3748889);

        // Kilometers
        // baseline all
        $query = new ParseQuery('PlaceObject');
        $query->near('location', $point);
        $results = $query->find();
        $this->assertEquals(3, count($results));

        // max with all
        $query = new ParseQuery('PlaceObject');
        $query->withinKilometers('location', $point, 4000.0);
        $results = $query->find();
        $this->assertEquals(3, count($results));

        // drop hawaii
        $query = new ParseQuery('PlaceObject');
        $query->withinKilometers('location', $point, 3700.0);
        $results = $query->find();
        $this->assertEquals(2, count($results));

        // drop sacramento
        $query = new ParseQuery('PlaceObject');
        $query->withinKilometers('location', $point, 100.0);
        $results = $query->find();
        $this->assertEquals(1, count($results));
        $this->assertEquals('San Francisco', $results[0]->get('name'));

        // drop SF
        $query = new ParseQuery('PlaceObject');
        $query->withinKilometers('location', $point, 10.0);
        $results = $query->find();
        $this->assertEquals(0, count($results));

        // Miles
        // max with all
        $query = new ParseQuery('PlaceObject');
        $query->withinMiles('location', $point, 2500.0);
        $results = $query->find();
        $this->assertEquals(3, count($results));

        // drop hawaii
        $query = new ParseQuery('PlaceObject');
        $query->withinMiles('location', $point, 2200.0);
        $results = $query->find();
        $this->assertEquals(2, count($results));

        // drop sacramento
        $query = new ParseQuery('PlaceObject');
        $query->withinMiles('location', $point, 75.0);
        $results = $query->find();
        $this->assertEquals(1, count($results));
        $this->assertEquals('San Francisco', $results[0]->get('name'));

        // drop SF
        $query = new ParseQuery('PlaceObject');
        $query->withinMiles('location', $point, 10.0);
        $results = $query->find();
        $this->assertEquals(0, count($results));
    }
}

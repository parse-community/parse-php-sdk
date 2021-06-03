<?php

namespace Parse\Test;

use Parse\ParseGeoPoint;
use Parse\ParseObject;
use Parse\ParseQuery;

use PHPUnit\Framework\TestCase;

class ParseGeoPointTest extends TestCase
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
        $this->assertEquals(44.0, $actualPoint->getLatitude());
        $this->assertEquals(-11.0, $actualPoint->getLongitude());

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
            $obj->set('index', $i);
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
        $this->assertEquals(1, $results[1]->get('index'));

        // 1
        $query = new ParseQuery('TestObject');
        $query->withinRadians('location', $point, 3.14 * 0.25);
        $results = $query->find();
        $this->assertEquals(1, count($results));
        $this->assertEquals(0, $results[0]->get('index'));
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

    public function testGeoMaxDistanceWithUnitsUnsorted()
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
        $query->withinKilometers('location', $point, 4000.0, false);
        $results = $query->find();
        $this->assertEquals(3, count($results));

        // drop hawaii
        $query = new ParseQuery('PlaceObject');
        $query->withinKilometers('location', $point, 3700.0, false);
        $results = $query->find();
        $this->assertEquals(2, count($results));

        // drop sacramento
        $query = new ParseQuery('PlaceObject');
        $query->withinKilometers('location', $point, 100.0, false);
        $results = $query->find();
        $this->assertEquals(1, count($results));
        $this->assertEquals('San Francisco', $results[0]->get('name'));

        // drop SF
        $query = new ParseQuery('PlaceObject');
        $query->withinKilometers('location', $point, 10.0, false);
        $results = $query->find();
        $this->assertEquals(0, count($results));

        // Miles
        // max with all
        $query = new ParseQuery('PlaceObject');
        $query->withinMiles('location', $point, 2500.0, false);
        $results = $query->find();
        $this->assertEquals(3, count($results));

        // drop hawaii
        $query = new ParseQuery('PlaceObject');
        $query->withinMiles('location', $point, 2200.0, false);
        $results = $query->find();
        $this->assertEquals(2, count($results));

        // drop sacramento
        $query = new ParseQuery('PlaceObject');
        $query->withinMiles('location', $point, 75.0, false);
        $results = $query->find();
        $this->assertEquals(1, count($results));
        $this->assertEquals('San Francisco', $results[0]->get('name'));

        // drop SF
        $query = new ParseQuery('PlaceObject');
        $query->withinMiles('location', $point, 10.0, false);
        $results = $query->find();
        $this->assertEquals(0, count($results));
    }

    public function testGeoQueriesUnsorted()
    {
        Helper::clearClass('PlaceObject');
        $sacramento = new ParseGeoPoint(38.52, -121.50);
        $obj = ParseObject::create('PlaceObject');
        $obj->set('location', $sacramento);
        $obj->set('name', 'Sacramento');
        $obj->save();

        $point = new ParseGeoPoint(37.6189722, -122.3748889);

        $query = new ParseQuery('PlaceObject');
        $query->withinRadians('location', $point, 3.14 * 2, false);
        $this->assertEquals($query->_getOptions(), [
            'where' => [
                'location' => [
                    '$geoWithin' => [
                        '$centerSphere' => [
                            [-122.3748889, 37.6189722],
                            3.14 * 2
                        ]
                    ]
                ]
            ]
        ]);
    }

    public function testBadLatitude()
    {
        $this->expectException(
            '\Parse\ParseException',
            'Latitude must be within range [-90.0, 90.0]'
        );
        new ParseGeoPoint(-180, 32);
    }

    public function testBadLongitude()
    {
        $this->expectException(
            '\Parse\ParseException',
            'Longitude must be within range [-180.0, 180.0]'
        );
        new ParseGeoPoint(32, -360);
    }

    public function testWithinPolygonOpenPath()
    {
        $inbound = ParseObject::create('TestObject');
        $onbound = ParseObject::create('TestObject');
        $outbound = ParseObject::create('TestObject');

        $inbound->set('location', new ParseGeoPoint(1, 1));
        $onbound->set('location', new ParseGeoPoint(10, 10));
        $outbound->set('location', new ParseGeoPoint(20, 20));

        ParseObject::saveAll([$inbound, $onbound, $outbound]);

        $points = [
            new ParseGeoPoint(0, 0),
            new ParseGeoPoint(0, 10),
            new ParseGeoPoint(10, 10),
            new ParseGeoPoint(10, 0)
        ];
        $query = new ParseQuery('TestObject');
        $query->withinPolygon('location', $points);
        $results = $query->find();
        $this->assertEquals(2, count($results));
    }

    public function testWithinPolygonClosedPath()
    {
        $inbound = ParseObject::create('TestObject');
        $onbound = ParseObject::create('TestObject');
        $outbound = ParseObject::create('TestObject');

        $inbound->set('location', new ParseGeoPoint(1, 1));
        $onbound->set('location', new ParseGeoPoint(10, 10));
        $outbound->set('location', new ParseGeoPoint(20, 20));

        ParseObject::saveAll([$inbound, $onbound, $outbound]);

        $points = [
            new ParseGeoPoint(0, 0),
            new ParseGeoPoint(0, 10),
            new ParseGeoPoint(10, 10),
            new ParseGeoPoint(10, 0),
            new ParseGeoPoint(0, 0)
        ];
        $query = new ParseQuery('TestObject');
        $query->withinPolygon('location', $points);
        $results = $query->find();
        $this->assertEquals(2, count($results));
    }

    public function testWithinPolygonEmpty()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('location', new ParseGeoPoint(1.5, 1.5));
        $obj->save();

        $this->expectException(
            '\Parse\ParseException',
            'bad $geoWithin value; $polygon should contain at least 3 GeoPoints'
        );
        $query = new ParseQuery('TestObject');
        $query->withinPolygon('location', []);
        $query->find();
    }

    public function testWithinPolygonTwoGeoPoints()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('location', new ParseGeoPoint(1.5, 1.5));
        $obj->save();

        $this->expectException(
            '\Parse\ParseException',
            'bad $geoWithin value; $polygon should contain at least 3 GeoPoints'
        );
        $points = [
            new ParseGeoPoint(0, 0),
            new ParseGeoPoint(10, 10)
        ];
        $query = new ParseQuery('TestObject');
        $query->withinPolygon('location', $points);
        $query->find();
    }

    public function testWithinPolygonNonArray()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('location', new ParseGeoPoint(1.5, 1.5));
        $obj->save();

        $this->expectException(
            '\Parse\ParseException',
            'bad $geoWithin value; $polygon should be Polygon object or Array of Parse.GeoPoint\'s'
        );
        $query = new ParseQuery('TestObject');
        $query->withinPolygon('location', 1234);
        $query->find();
    }

    public function testWithinPolygonInvalidArray()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('location', new ParseGeoPoint(1.5, 1.5));
        $obj->save();

        $this->expectException(
            '\Parse\ParseException',
            'bad $geoWithin value; $polygon should contain at least 3 GeoPoints'
        );
        $query = new ParseQuery('TestObject');
        $query->withinPolygon('location', [$obj]);
        $query->find();
    }
}

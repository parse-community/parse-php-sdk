<?php

namespace Parse\Test;

use Parse\ParseGeoPoint;
use Parse\ParsePolygon;
use Parse\ParseObject;
use Parse\ParseQuery;

class ParsePolygonTest extends \PHPUnit_Framework_TestCase
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

    public function testPolygonWithPoints()
    {
        $openPoints = [[0,0],[0,1],[1,1],[1,0]];
        $closedPoints = [[0,0],[0,1],[1,1],[1,0],[0,0]];
        $polygon = new ParsePolygon($openPoints);

        $obj = ParseObject::create('TestObject');
        $obj->set('polygon', $polygon);
        $obj->save();

        // Query by open points
        $query = new ParseQuery('TestObject');
        $query->equalTo('polygon', $polygon);

        $results = $query->find();
        $actualPolygon = $results[0]->get('polygon');

        $this->assertEquals(1, count($results));
        $this->assertEquals($closedPoints, $actualPolygon->getCoordinates());

        // Query by closed points
        $polygon = new ParsePolygon($closedPoints);
        $query = new ParseQuery('TestObject');
        $query->equalTo('polygon', $polygon);

        $results = $query->find();
        $actualPolygon = $results[0]->get('polygon');

        $this->assertEquals(1, count($results));
        $this->assertEquals($closedPoints, $actualPolygon->getCoordinates());
    }

    public function testPolygonWithGeoPoints()
    {
        $p1 = new ParseGeoPoint(0, 0);
        $p2 = new ParseGeoPoint(0, 1);
        $p3 = new ParseGeoPoint(1, 1);
        $p4 = new ParseGeoPoint(1, 0);
        $p5 = new ParseGeoPoint(0, 0);

        $points = [$p1, $p2, $p3, $p4];
        $openPoints = [[0,0],[0,1],[1,1],[1,0]];
        $closedPoints = [[0,0],[0,1],[1,1],[1,0],[0,0]];
        $polygon = new ParsePolygon($points);

        $obj = ParseObject::create('TestObject');
        $obj->set('polygon', $polygon);
        $obj->save();

        // Query by open points
        $query = new ParseQuery('TestObject');
        $query->equalTo('polygon', $polygon);

        $results = $query->find();
        $actualPolygon = $results[0]->get('polygon');

        $this->assertEquals(1, count($results));
        $this->assertEquals($closedPoints, $actualPolygon->getCoordinates());

        // Query by closed points
        $polygon = new ParsePolygon($closedPoints);
        $query = new ParseQuery('TestObject');
        $query->equalTo('polygon', $polygon);

        $results = $query->find();
        $actualPolygon = $results[0]->get('polygon');

        $this->assertEquals(1, count($results));
        $this->assertEquals($closedPoints, $actualPolygon->getCoordinates());
    }

    public function testPolygonMinimum()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'Polygon must have at least 3 GeoPoints or Points'
        );
        $polygon = new ParsePolygon([[0,0]]);
        $obj = ParseObject::create('TestObject');
        $obj->set('polygon', $polygon);
        $obj->save();
    }

    public function testPolygonInvalidInput()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'Coordinates must be an Array'
        );
        $polygon = new ParsePolygon(1234);
        $obj = ParseObject::create('TestObject');
        $obj->set('polygon', $polygon);
        $obj->save();
    }

    public function testPolygonInvalidArray()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'Coordinates must be an Array of GeoPoints or Points'
        );
        $polygon = new ParsePolygon([['str1'],['str2'],['str3']]);
        $obj = ParseObject::create('TestObject');
        $obj->set('polygon', $polygon);
        $obj->save();
    }

    public function testPolygonContains()
    {
        $points1 = [[0,0],[0,1],[1,1],[1,0]];
        $points2 = [[0,0],[0,2],[2,2],[2,0]];
        $points3 = [[10,10],[10,15],[15,15],[15,10],[10,10]];

        $polygon1 = new ParsePolygon($points1);
        $polygon2 = new ParsePolygon($points2);
        $polygon3 = new ParsePolygon($points3);

        $obj1 = ParseObject::create('TestObject');
        $obj2 = ParseObject::create('TestObject');
        $obj3 = ParseObject::create('TestObject');

        $obj1->set('polygon', $polygon1);
        $obj2->set('polygon', $polygon2);
        $obj3->set('polygon', $polygon3);

        ParseObject::saveAll([$obj1, $obj2, $obj3]);

        $point = new ParseGeoPoint(0.5, 0.5);
        $query = new ParseQuery('TestObject');
        $query->polygonContains('polygon', $point);
        $results = $query->find();
        $this->assertEquals(2, count($results));
    }

    public function testPolygonContainsInvalidInput()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'bad $geoIntersect value; $point should be GeoPoint'
        );
        $points = [[0,0],[0,1],[1,1],[1,0]];
        $polygon = new ParsePolygon($points);
        $obj = ParseObject::create('TestObject');
        $obj->set('polygon', $polygon);
        $obj->save();

        $query = new ParseQuery('TestObject');
        $query->polygonContains('polygon', 1234);
        $results = $query->find();
    }
}

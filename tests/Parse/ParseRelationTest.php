<?php

namespace Parse\Test;

use Parse\ParseObject;
use Parse\ParseQuery;

class ParseRelationTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Helper::setUp();
    }

    public function tearDown()
    {
        Helper::tearDown();
    }

    /**
     * This function used as a helper function in test functions to save objects.
     *
     * @param int      $numberOfObjects Number of objects you want to save.
     * @param callable $callback        Function which takes int as a parameter.
     *                                  and should return ParseObject.
     */
    public function saveObjects($numberOfObjects, $callback)
    {
        $allObjects = [];
        for ($i = 0; $i < $numberOfObjects; $i++) {
            $allObjects[] = $callback($i);
        }
        ParseObject::saveAll($allObjects);
    }

    public function testParseRelations()
    {
        $children = [];
        $this->saveObjects(
            10,
            function ($i) use (&$children) {
                $child = ParseObject::create('ChildObject');
                $child->set('x', $i);
                $children[] = $child;

                return $child;
            }
        );
        $parent = ParseObject::create('ParentObject');
        $relation = $parent->getRelation('children');
        $relation->add($children[0]);
        $parent->set('foo', 1);
        $parent->save();

        $results = $relation->getQuery()->find();
        $this->assertEquals(1, count($results));
        $this->assertEquals($children[0]->getObjectId(), $results[0]->getObjectId());
        $this->assertFalse($parent->isDirty());

        $parentAgain = (new ParseQuery('ParentObject'))->get($parent->getObjectId());
        $relationAgain = $parentAgain->get('children');
        $this->assertNotNull($relationAgain, 'Error');

        $results = $relation->getQuery()->find();
        $this->assertEquals(1, count($results));
        $this->assertEquals($children[0]->getObjectId(), $results[0]->getObjectId());

        $relation->remove($children[0]);
        $relation->add([$children[4], $children[5]]);
        $parent->set('bar', 3);
        $parent->save();

        $results = $relation->getQuery()->find();
        $this->assertEquals(2, count($results));
        $this->assertFalse($parent->isDirty());

        $relation->remove($children[5]);
        $relation->add(
            [
                $children[5],
                $children[6],
                $children[7],
                $children[8],
            ]
        );
        $parent->save();

        $results = $relation->getQuery()->find();
        $this->assertEquals(5, count($results));
        $this->assertFalse($parent->isDirty());

        $relation->remove($children[8]);
        $parent->save();
        $results = $relation->getQuery()->find();
        $this->assertEquals(4, count($results));
        $this->assertFalse($parent->isDirty());

        $query = $relation->getQuery();
        $query->lessThan('x', 5);
        $results = $query->find();
        $this->assertEquals(1, count($results));
        $this->assertEquals($children[4]->getObjectId(), $results[0]->getObjectId());
    }

    public function testQueriesOnRelationFields()
    {
        $children = [];
        $this->saveObjects(
            10,
            function ($i) use (&$children) {
                $child = ParseObject::create('ChildObject');
                $child->set('x', $i);
                $children[] = $child;

                return $child;
            }
        );

        $parent = ParseObject::create('ParentObject');
        $parent->set('x', 4);
        $relation = $parent->getRelation('children');
        $relation->add(
            [
                $children[0],
                $children[1],
                $children[2],
            ]
        );
        $parent->save();
        $parent2 = ParseObject::create('ParentObject');
        $parent2->set('x', 3);
        $relation2 = $parent2->getRelation('children');
        $relation2->add(
            [
                $children[4],
                $children[5],
                $children[6],
            ]
        );
        $parent2->save();
        $query = new ParseQuery('ParentObject');
        $query->containedIn('children', [$children[4], $children[9]]);
        $results = $query->find();
        $this->assertEquals(1, count($results));
        $this->assertEquals($results[0]->getObjectId(), $parent2->getObjectId());
    }
}

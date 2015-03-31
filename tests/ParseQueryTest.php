<?php

use Parse\ParseACL;
use Parse\ParseException;
use Parse\ParseObject;
use Parse\ParseQuery;

require_once 'ParseTestHelper.php';

class ParseQueryTest extends PHPUnit_Framework_TestCase
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

    public function provideTestObjects($numberOfObjects)
    {
        $this->saveObjects(
            $numberOfObjects, function ($i) {
                $obj = ParseObject::create('TestObject');
                $obj->set('foo', 'bar'.$i);

                return $obj;
            }
        );
    }

    public function testBasicQuery()
    {
        $baz = new ParseObject("TestObject");
        $baz->set("foo", "baz");
        $qux = new ParseObject("TestObject");
        $qux->set("foo", "qux");
        $baz->save();
        $qux->save();

        $query = new ParseQuery("TestObject");
        $query->equalTo("foo", "baz");
        $results = $query->find();
        $this->assertEquals(
            1, count($results),
            'Did not find object.'
        );
        $this->assertEquals(
            "baz", $results[0]->get("foo"),
            'Did not return the correct object.'
        );
    }

    public function testQueryWithLimit()
    {
        $baz = new ParseObject("TestObject");
        $baz->set("foo", "baz");
        $qux = new ParseObject("TestObject");
        $qux->set("foo", "qux");
        $baz->save();
        $qux->save();

        $query = new ParseQuery("TestObject");
        $query->limit(1);
        $results = $query->find();
        $this->assertEquals(
            1, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testEqualTo()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->save();
        $query = new ParseQuery('TestObject');
        $query->equalTo('objectId', $obj->getObjectId());
        $results = $query->find();
        $this->assertTrue(count($results) == 1, 'Did not find object.');
    }

    public function testNotEqualTo()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->notEqualTo('foo', 'bar9');
        $results = $query->find();
        $this->assertEquals(
            count($results), 9,
            'Did not find 9 objects, found '.count($results)
        );
    }

    public function testLessThan()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->lessThan('foo', 'bar1');
        $results = $query->find();
        $this->assertEquals(
            count($results), 1,
            'LessThan function did not return correct number of objects.'
        );
        $this->assertEquals(
            $results[0]->get('foo'), 'bar0',
            'LessThan function did not return the correct object'
        );
    }

    public function testLessThanOrEqualTo()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->lessThanOrEqualTo('foo', 'bar0');
        $results = $query->find();
        $this->assertEquals(
            count($results), 1,
            'LessThanOrEqualTo function did not return correct number of objects.'
        );
        $this->assertEquals(
            $results[0]->get('foo'), 'bar0',
            'LessThanOrEqualTo function did not return the correct object.'
        );
    }

    public function testStartsWithSingle()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->startsWith('foo', 'bar0');
        $results = $query->find();
        $this->assertEquals(
            count($results), 1,
            'StartsWith function did not return correct number of objects.'
        );
        $this->assertEquals(
            $results[0]->get('foo'), 'bar0',
            'StartsWith function did not return the correct object.'
        );
    }

    public function testStartsWithMultiple()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->startsWith('foo', 'bar');
        $results = $query->find();
        $this->assertEquals(
            count($results), 10,
            'StartsWith function did not return correct number of objects.'
        );
    }

    public function testStartsWithMiddle()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->startsWith('foo', 'ar');
        $results = $query->find();
        $this->assertEquals(
            count($results), 0,
            'StartsWith function did not return correct number of objects.'
        );
    }

    public function testStartsWithRegexDelimiters()
    {
        $testObject = ParseObject::create("TestObject");
        $testObject->set("foo", "foob\E");
        $testObject->save();
        $query = new ParseQuery('TestObject');
        $query->startsWith('foo', 'foob\E');
        $results = $query->find();
        $this->assertEquals(
            count($results), 1,
            'StartsWith function did not return correct number of objects.'
        );
        $query->startsWith('foo', 'foobE');
        $results = $query->find();
        $this->assertEquals(
            count($results), 0,
            'StartsWith function did not return correct number of objects.'
        );
    }

    public function testStartsWithRegexDot()
    {
        $testObject = ParseObject::create("TestObject");
        $testObject->set("foo", "foobarfoo");
        $testObject->save();
        $query = new ParseQuery('TestObject');
        $query->startsWith('foo', 'foo(.)*');
        $results = $query->find();
        $this->assertEquals(
            count($results), 0,
            'StartsWith function did not return correct number of objects.'
        );
        $query->startsWith('foo', 'foo.*');
        $results = $query->find();
        $this->assertEquals(
            count($results), 0,
            'StartsWith function did not return correct number of objects.'
        );
        $query->startsWith('foo', 'foo');
        $results = $query->find();
        $this->assertEquals(
            count($results), 1,
            'StartsWith function did not return correct number of objects.'
        );
    }

    public function testStartsWithRegexSlash()
    {
        $testObject = ParseObject::create("TestObject");
        $testObject->set("foo", "foobarfoo");
        $testObject->save();
        $query = new ParseQuery('TestObject');
        $query->startsWith('foo', 'foo/bar');
        $results = $query->find();
        $this->assertEquals(
            count($results), 0,
            'StartsWith function did not return correct number of objects.'
        );
        $query->startsWith('foo', 'foobar');
        $results = $query->find();
        $this->assertEquals(
            count($results), 1,
            'StartsWith function did not return correct number of objects.'
        );
    }

    public function testStartsWithRegexQuestionmark()
    {
        $testObject = ParseObject::create("TestObject");
        $testObject->set("foo", "foobarfoo");
        $testObject->save();
        $query = new ParseQuery('TestObject');
        $query->startsWith('foo', 'foox?bar');
        $results = $query->find();
        $this->assertEquals(
            count($results), 0,
            'StartsWith function did not return correct number of objects.'
        );
        $query->startsWith('foo', 'foo(x)?bar');
        $results = $query->find();
        $this->assertEquals(
            count($results), 0,
            'StartsWith function did not return correct number of objects.'
        );
        $query->startsWith('foo', 'foobar');
        $results = $query->find();
        $this->assertEquals(
            count($results), 1,
            'StartsWith function did not return correct number of objects.'
        );
    }

    public function testGreaterThan()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->greaterThan('foo', 'bar8');
        $results = $query->find();
        $this->assertEquals(
            count($results), 1,
            'GreaterThan function did not return correct number of objects.'
        );
        $this->assertEquals(
            $results[0]->get('foo'), 'bar9',
            'GreaterThan function did not return the correct object.'
        );
    }

    public function testGreaterThanOrEqualTo()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->greaterThanOrEqualTo('foo', 'bar9');
        $results = $query->find();
        $this->assertEquals(
            count($results), 1,
            'GreaterThanOrEqualTo function did not return correct number of objects.'
        );
        $this->assertEquals(
            $results[0]->get('foo'), 'bar9',
            'GreaterThanOrEqualTo function did not return the correct object.'
        );
    }

    public function testLessThanOrEqualGreaterThanOrEqual()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->lessThanOrEqualTo('foo', 'bar4');
        $query->greaterThanOrEqualTo('foo', 'bar2');
        $results = $query->find();
        $this->assertEquals(
            3, count($results),
            'LessThanGreaterThan did not return correct number of objects.'
        );
    }

    public function testLessThanGreaterThan()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->lessThan('foo', 'bar5');
        $query->greaterThan('foo', 'bar3');
        $results = $query->find();
        $this->assertEquals(
            1, count($results),
            'LessThanGreaterThan did not return correct number of objects.'
        );
        $this->assertEquals(
            'bar4', $results[0]->get('foo'),
            'LessThanGreaterThan did not return the correct object.'
        );
    }

    public function testObjectIdEqualTo()
    {
        ParseTestHelper::clearClass("BoxedNumber");
        $boxedNumberArray = [];
        $this->saveObjects(
            5, function ($i) use (&$boxedNumberArray) {
                $boxedNumber = new ParseObject("BoxedNumber");
                $boxedNumber->set("number", $i);
                $boxedNumberArray[] = $boxedNumber;

                return $boxedNumber;
            }
        );
        $query = new ParseQuery("BoxedNumber");
        $query->equalTo("objectId", $boxedNumberArray[4]->getObjectId());
        $results = $query->find();
        $this->assertEquals(
            1, count($results),
            'Did not find object.'
        );
        $this->assertEquals(
            4, $results[0]->get("number"),
            'Did not return the correct object.'
        );
    }

    public function testFindNoElements()
    {
        ParseTestHelper::clearClass("BoxedNumber");
        $this->saveObjects(
            5, function ($i) {
                $boxedNumber = new ParseObject("BoxedNumber");
                $boxedNumber->set("number", $i);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery("BoxedNumber");
        $query->equalTo("number", 17);
        $results = $query->find();
        $this->assertEquals(
            0, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testFindWithError()
    {
        $query = new ParseQuery("TestObject");
        $this->setExpectedException('Parse\ParseException', 'Invalid key', 102);
        $query->equalTo('$foo', 'bar');
        $query->find();
    }

    public function testGet()
    {
        $testObj = ParseObject::create("TestObject");
        $testObj->set("foo", "bar");
        $testObj->save();
        $query = new ParseQuery("TestObject");
        $result = $query->get($testObj->getObjectId());
        $this->assertEquals(
            $testObj->getObjectId(), $result->getObjectId(),
            'Did not return the correct object.'
        );
        $this->assertEquals(
            "bar", $result->get("foo"),
            'Did not return the correct object.'
        );
    }

    public function testGetError()
    {
        $obj = ParseObject::create("TestObject");
        $obj->set('foo', 'bar');
        $obj->save();
        $query = new ParseQuery("TestObject");
        $this->setExpectedException('Parse\ParseException', 'Object not found', 101);
        $query->get("InvalidObjectID");
    }

    public function testGetNull()
    {
        $obj = ParseObject::create("TestObject");
        $obj->set('foo', 'bar');
        $obj->save();
        $query = new ParseQuery("TestObject");
        $this->setExpectedException('Parse\ParseException', 'Object not found', 101);
        $query->get(null);
    }

    public function testFirst()
    {
        $testObject = ParseObject::create("TestObject");
        $testObject->set("foo", "bar");
        $testObject->save();
        $query = new ParseQuery("TestObject");
        $query->equalTo("foo", "bar");
        $result = $query->first();
        $this->assertEquals(
            "bar", $result->get("foo"),
            'Did not return the correct object.'
        );
    }

    public function testFirstWithError()
    {
        $query = new ParseQuery("TestObject");
        $query->equalTo('$foo', 'bar');
        $this->setExpectedException('Parse\ParseException', 'Invalid key', 102);
        $query->first();
    }

    public function testFirstNoResult()
    {
        $testObject = ParseObject::create("TestObject");
        $testObject->set("foo", "bar");
        $testObject->save();
        $query = new ParseQuery("TestObject");
        $query->equalTo("foo", "baz");
        $result = $query->first();
        $this->assertTrue(
            empty($result),
            'Did not return correct number of objects.'
        );
    }

    public function testFirstWithTwoResults()
    {
        $this->saveObjects(
            2, function ($i) {
                $testObject = ParseObject::create("TestObject");
                $testObject->set("foo", "bar");

                return $testObject;
            }
        );
        $query = new ParseQuery("TestObject");
        $query->equalTo("foo", "bar");
        $result = $query->first();
        $this->assertEquals(
            "bar", $result->get("foo"),
            'Did not return the correct object.'
        );
    }

    public function testNotEqualToObject()
    {
        ParseTestHelper::clearClass("Container");
        ParseTestHelper::clearClass("Item");
        $items = [];
        $this->saveObjects(
            2, function ($i) use (&$items) {
                $items[] = ParseObject::create("Item");

                return $items[$i];
            }
        );
        $this->saveObjects(
            2, function ($i) use ($items) {
                $container = ParseObject::create("Container");
                $container->set("item", $items[$i]);

                return $container;
            }
        );
        $query = new ParseQuery("Container");
        $query->notEqualTo("item", $items[0]);
        $result = $query->find();
        $this->assertEquals(
            1, count($result),
            'Did not return the correct object.'
        );
    }

    public function testSkip()
    {
        $this->saveObjects(
            2, function ($i) {
                return ParseObject::create("TestObject");
            }
        );
        $query = new ParseQuery("TestObject");
        $query->skip(1);
        $result = $query->find();
        $this->assertEquals(
            1, count($result),
            'Did not return the correct object.'
        );
        $query->skip(3);
        $result = $query->find();
        $this->assertEquals(
            0, count($result),
            'Did not return the correct object.'
        );
    }

    public function testSkipDoesNotAffectCount()
    {
        $this->saveObjects(
            2, function ($i) {
                return ParseObject::create("TestObject");
            }
        );
        $query = new ParseQuery("TestObject");
        $count = $query->count();
        $this->assertEquals(
            2, $count,
            'Did not return correct number of objects.'
        );
        $query->skip(1);
        $this->assertEquals(
            2, $count,
            'Did not return correct number of objects.'
        );
        $query->skip(3);
        $this->assertEquals(
            2, $count,
            'Did not return correct number of objects.'
        );
    }

    public function testCount()
    {
        ParseTestHelper::clearClass("BoxedNumber");
        $this->saveObjects(
            3, function ($i) {
                $boxedNumber = ParseObject::create("BoxedNumber");
                $boxedNumber->set("x", $i + 1);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery("BoxedNumber");
        $query->greaterThan("x", 1);
        $count = $query->count();
        $this->assertEquals(
            2, $count,
            'Did not return correct number of objects.'
        );
    }

    public function testCountError()
    {
        $query = new ParseQuery("Test");
        $query->equalTo('$foo', "bar");
        $this->setExpectedException('Parse\ParseException', 'Invalid key', 102);
        $query->count();
    }

    public function testOrderByAscendingNumber()
    {
        ParseTestHelper::clearClass("BoxedNumber");
        $numbers = [3, 1, 2];
        $this->saveObjects(
            3, function ($i) use ($numbers) {
                $boxedNumber = ParseObject::create("BoxedNumber");
                $boxedNumber->set("number", $numbers[$i]);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery("BoxedNumber");
        $query->ascending("number");
        $results = $query->find();
        $this->assertEquals(
            3, count($results),
            'Did not return correct number of objects.'
        );
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals(
                $i + 1, $results[$i]->get("number"),
                'Did not return the correct object.'
            );
        }
    }

    public function testOrderByDescendingNumber()
    {
        ParseTestHelper::clearClass("BoxedNumber");
        $numbers = [3, 1, 2];
        $this->saveObjects(
            3, function ($i) use ($numbers) {
                $boxedNumber = ParseObject::create("BoxedNumber");
                $boxedNumber->set("number", $numbers[$i]);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery("BoxedNumber");
        $query->descending("number");
        $results = $query->find();
        $this->assertEquals(
            3, count($results),
            'Did not return correct number of objects.'
        );
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals(
                3 - $i, $results[$i]->get("number"),
                'Did not return the correct object.'
            );
        }
    }

    public function provideTestObjectsForQuery($numberOfObjects)
    {
        $this->saveObjects(
            $numberOfObjects, function ($i) {
                $parent = ParseObject::create("ParentObject");
                $child = ParseObject::create("ChildObject");
                $child->set("x", $i);
                $parent->set("x", 10 + $i);
                $parent->set("child", $child);

                return $parent;
            }
        );
    }

    public function testMatchesQuery()
    {
        ParseTestHelper::clearClass("ChildObject");
        ParseTestHelper::clearClass("ParentObject");
        $this->provideTestObjectsForQuery(10);
        $subQuery = new ParseQuery("ChildObject");
        $subQuery->greaterThan("x", 5);
        $query = new ParseQuery("ParentObject");
        $query->matchesQuery("child", $subQuery);
        $results = $query->find();

        $this->assertEquals(
            4, count($results),
            'Did not return correct number of objects.'
        );
        foreach ($results as $parentObj) {
            $this->assertGreaterThan(
                15, $parentObj->get("x"),
                'Did not return the correct object.'
            );
        }
    }

    public function testDoesNotMatchQuery()
    {
        ParseTestHelper::clearClass("ChildObject");
        ParseTestHelper::clearClass("ParentObject");
        $this->provideTestObjectsForQuery(10);
        $subQuery = new ParseQuery("ChildObject");
        $subQuery->greaterThan("x", 5);
        $query = new ParseQuery("ParentObject");
        $query->doesNotMatchQuery("child", $subQuery);
        $results = $query->find();

        $this->assertEquals(
            6, count($results),
            'Did not return the correct object.'
        );
        foreach ($results as $parentObj) {
            $this->assertLessThanOrEqual(
                15, $parentObj->get("x"),
                'Did not return the correct object.'
            );
            $this->assertGreaterThanOrEqual(
                10, $parentObj->get("x"),
                'Did not return the correct object.'
            );
        }
    }

    public function provideTestObjectsForKeyInQuery()
    {
        ParseTestHelper::clearClass("Restaurant");
        ParseTestHelper::clearClass("Person");
        $restaurantLocations = ["Djibouti", "Ouagadougou"];
        $restaurantRatings = [5, 3];
        $numberOFRestaurantObjects = count($restaurantLocations);

        $personHomeTown = ["Djibouti", "Ouagadougou", "Detroit"];
        $personName = ["Bob", "Tom", "Billy"];
        $numberOfPersonObjects = count($personHomeTown);

        $this->saveObjects(
            $numberOFRestaurantObjects, function ($i) use ($restaurantRatings, $restaurantLocations) {
                $restaurant = ParseObject::create("Restaurant");
                $restaurant->set("ratings", $restaurantRatings[$i]);
                $restaurant->set("location", $restaurantLocations[$i]);

                return $restaurant;
            }
        );

        $this->saveObjects(
            $numberOfPersonObjects, function ($i) use ($personHomeTown, $personName) {
                $person = ParseObject::create("Person");
                $person->set("hometown", $personHomeTown[$i]);
            $person->set("name", $personName[$i]);

                return $person;
            }
        );
    }

    public function testMatchesKeyInQuery()
    {
        $this->provideTestObjectsForKeyInQuery();
        $subQuery = new ParseQuery("Restaurant");
        $subQuery->greaterThan("ratings", 4);

        $query = new ParseQuery("Person");
        $query->matchesKeyInQuery("hometown", "location", $subQuery);
        $results = $query->find();

        $this->assertEquals(
            1, count($results),
            'Did not return correct number of objects.'
        );
        $this->assertEquals(
            "Bob", $results[0]->get("name"),
            'Did not return the correct object.'
        );
    }

    public function testDoesNotMatchKeyInQuery()
    {
        $this->provideTestObjectsForKeyInQuery();
        $subQuery = new ParseQuery("Restaurant");
        $subQuery->greaterThanOrEqualTo("ratings", 3);

        $query = new ParseQuery("Person");
        $query->doesNotmatchKeyInQuery("hometown", "location", $subQuery);
        $results = $query->find();

        $this->assertEquals(
            1, count($results),
            'Did not return correct number of objects.'
        );
        $this->assertEquals(
            "Billy", $results[0]->get("name"),
            'Did not return the correct object.'
        );
    }

    public function testOrQueries()
    {
        $this->provideTestObjects(10);
        $subQuery1 = new ParseQuery("TestObject");
        $subQuery1->lessThan("foo", "bar2");
        $subQuery2 = new ParseQuery("TestObject");
        $subQuery2->greaterThan("foo", "bar5");

        $mainQuery = ParseQuery::orQueries([$subQuery1, $subQuery2]);
        $results = $mainQuery->find();
        $length = count($results);
        $this->assertEquals(
            6, $length,
            'Did not return correct number of objects.'
        );
        for ($i = 0; $i < $length; $i++) {
            $this->assertTrue(
                $results[$i]->get("foo") < "bar2" ||
                $results[$i]->get("foo") > "bar5",
                'Did not return the correct object.'
            );
        }
    }

    public function testComplexQueries()
    {
        ParseTestHelper::clearClass("Child");
        ParseTestHelper::clearClass("Parent");
        $this->saveObjects(
            10, function ($i) {
                $child = new ParseObject("Child");
                $child->set("x", $i);
                $parent = new ParseObject("Parent");
                $parent->set("y", $i);
                $parent->set("child", $child);

                return $parent;
            }
        );
        $subQuery = new ParseQuery("Child");
        $subQuery->equalTo("x", 4);
        $query1 = new ParseQuery("Parent");
        $query1->matchesQuery("child", $subQuery);
        $query2 = new ParseQuery("Parent");
        $query2->lessThan("y", 2);

        $orQuery = ParseQuery::orQueries([$query1, $query2]);
        $results = $orQuery->find();
        $this->assertEquals(
            3, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testEach()
    {
        ParseTestHelper::clearClass("Object");
        $total = 50;
        $count = 25;
        $this->saveObjects(
            $total, function ($i) {
                $obj = new ParseObject("Object");
                $obj->set("x", $i + 1);

                return $obj;
            }
        );
        $query = new ParseQuery("Object");
        $query->lessThanOrEqualTo("x", $count);

        $values = [];
        $query->each(
            function ($obj) use (&$values) {
                $values[] = $obj->get("x");
            }, 10
        );

        $valuesLength = count($values);
        $this->assertEquals(
            $count, $valuesLength,
            'Did not return correct number of objects.'
        );
        sort($values);
        for ($i = 0; $i < $valuesLength; $i++) {
            $this->assertEquals(
                $i + 1, $values[$i],
                'Did not return the correct object.'
            );
        }
    }

    public function testEachFailsWithOrder()
    {
        ParseTestHelper::clearClass("Object");
        $total = 50;
        $count = 25;
        $this->saveObjects(
            $total, function ($i) {
                $obj = new ParseObject("Object");
                $obj->set("x", $i + 1);

                return $obj;
            }
        );
        $query = new ParseQuery("Object");
        $query->lessThanOrEqualTo("x", $count);
        $query->ascending("x");
        $this->setExpectedException('\Exception', 'sort');
        $query->each(
            function ($obj) {
            }
        );
    }

    public function testEachFailsWithSkip()
    {
        $total = 50;
        $count = 25;
        $this->saveObjects(
            $total, function ($i) {
                $obj = new ParseObject("Object");
                $obj->set("x", $i + 1);

                return $obj;
            }
        );
        $query = new ParseQuery("Object");
        $query->lessThanOrEqualTo("x", $count);
        $query->skip(5);
        $this->setExpectedException('\Exception', 'skip');
        $query->each(
            function ($obj) {
            }
        );
    }

    public function testEachFailsWithLimit()
    {
        $total = 50;
        $count = 25;
        $this->saveObjects(
            $total, function ($i) {
                $obj = new ParseObject("Object");
                $obj->set("x", $i + 1);

                return $obj;
            }
        );
        $query = new ParseQuery("Object");
        $query->lessThanOrEqualTo("x", $count);
        $query->limit(5);
        $this->setExpectedException('\Exception', 'limit');
        $query->each(
            function ($obj) {
            }
        );
    }

    public function testContainsAllNumberArrayQueries()
    {
        ParseTestHelper::clearClass("NumberSet");
        $numberSet1 = new ParseObject("NumberSet");
        $numberSet1->setArray("numbers", [1, 2, 3, 4, 5]);
        $numberSet2 = new ParseObject("NumberSet");
        $numberSet2->setArray("numbers", [1, 3, 4, 5]);
        $numberSet1->save();
        $numberSet2->save();

        $query = new ParseQuery("NumberSet");
        $query->containsAll("numbers", [1, 2, 3]);
        $results = $query->find();
        $this->assertEquals(
            1, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testContainsAllStringArrayQueries()
    {
        ParseTestHelper::clearClass("StringSet");
        $stringSet1 = new ParseObject("StringSet");
        $stringSet1->setArray("strings", ["a", "b", "c", "d", "e"]);
        $stringSet1->save();
        $stringSet2 = new ParseObject("StringSet");
        $stringSet2->setArray("strings", ["a", "c", "d", "e"]);
        $stringSet2->save();

        $query = new ParseQuery("StringSet");
        $query->containsAll("strings", ["a", "b", "c"]);
        $results = $query->find();
        $this->assertEquals(
            1, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testContainsAllDateArrayQueries()
    {
        ParseTestHelper::clearClass("DateSet");
        $dates1 = [
                new DateTime("2013-02-01T00:00:00Z"),
                new DateTime("2013-02-02T00:00:00Z"),
                new DateTime("2013-02-03T00:00:00Z"),
                new DateTime("2013-02-04T00:00:00Z"),
        ];
        $dates2 = [
                new DateTime("2013-02-01T00:00:00Z"),
                new DateTime("2013-02-03T00:00:00Z"),
                new DateTime("2013-02-04T00:00:00Z"),
        ];

        $obj1 = ParseObject::create("DateSet");
        $obj1->setArray("dates", $dates1);
        $obj1->save();
        $obj2 = ParseObject::create("DateSet");
        $obj2->setArray("dates", $dates2);
        $obj2->save();

        $query = new ParseQuery("DateSet");
        $query->containsAll(
            "dates", [
                new DateTime("2013-02-01T00:00:00Z"),
                new DateTime("2013-02-02T00:00:00Z"),
                new DateTime("2013-02-03T00:00:00Z"),
            ]
        );
        $result = $query->find();
        $this->assertEquals(
            1, count($result),
            'Did not return correct number of objects.'
        );
    }

    public function testContainsAllObjectArrayQueries()
    {
        ParseTestHelper::clearClass("MessageSet");
        $messageList = [];
        $this->saveObjects(
            4, function ($i) use (&$messageList) {
                $messageList[] = ParseObject::create("TestObject");
                $messageList[$i]->set("i", $i);

                return $messageList[$i];
            }
        );
        $messageSet1 = ParseObject::create("MessageSet");
        $messageSet1->setArray("messages", $messageList);
        $messageSet1->save();
        $messageSet2 = ParseObject::create("MessageSet");
        $messageSet2->setArray(
            "message",
            [$messageList[0], $messageList[1], $messageList[3]]
        );
        $messageSet2->save();

        $query = new ParseQuery("MessageSet");
        $query->containsAll("messages", [$messageList[0], $messageList[2]]);
        $results = $query->find();
        $this->assertEquals(
            1, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testContainedInObjectArrayQueries()
    {
        $messageList = [];
        $this->saveObjects(
            4, function ($i) use (&$messageList) {
                $message = ParseObject::create("TestObject");
                if ($i > 0) {
                    $message->set("prior", $messageList[$i - 1]);
                }
                $messageList[] = $message;

                return $message;
            }
        );
        $query = new ParseQuery("TestObject");
        $query->containedIn("prior", [$messageList[0], $messageList[2]]);
        $results = $query->find();
        $this->assertEquals(
            2, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testContainedInQueries()
    {
        ParseTestHelper::clearClass("BoxedNumber");
        $this->saveObjects(
            10, function ($i) {
                $boxedNumber = ParseObject::create("BoxedNumber");
                $boxedNumber->set("number", $i);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery("BoxedNumber");
        $query->containedIn("number", [3, 5, 7, 9, 11]);
        $results = $query->find();
        $this->assertEquals(
            4, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testNotContainedInQueries()
    {
        ParseTestHelper::clearClass("BoxedNumber");
        $this->saveObjects(
            10, function ($i) {
                $boxedNumber = ParseObject::create("BoxedNumber");
                $boxedNumber->set("number", $i);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery("BoxedNumber");
        $query->notContainedIn("number", [3, 5, 7, 9, 11]);
        $results = $query->find();
        $this->assertEquals(
            6, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testObjectIdContainedInQueries()
    {
        ParseTestHelper::clearClass("BoxedNumber");
        $objects = [];
        $this->saveObjects(
            5, function ($i) use (&$objects) {
                $boxedNumber = ParseObject::create("BoxedNumber");
                $boxedNumber->set("number", $i);
                $objects[] = $boxedNumber;

                return $boxedNumber;
            }
        );
        $query = new ParseQuery("BoxedNumber");
        $query->containedIn(
            "objectId", [$objects[2]->getObjectId(),
                        $objects[3]->getObjectId(),
                        $objects[0]->getObjectId(),
                        "NONSENSE", ]
        );
        $query->ascending("number");
        $results = $query->find();
        $this->assertEquals(
            3, count($results),
            'Did not return correct number of objects.'
        );
        $this->assertEquals(
            0, $results[0]->get("number"),
            'Did not return the correct object.'
        );
        $this->assertEquals(
            2, $results[1]->get("number"),
            'Did not return the correct object.'
        );
        $this->assertEquals(
            3, $results[2]->get("number"),
            'Did not return the correct object.'
        );
    }

    public function testStartsWith()
    {
        $someAscii = "\\E' !\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTU".
                "VWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~'";
        $prefixes = ['zax', 'start', '', ''];
        $suffixes = ['qub', '', 'end', ''];
        $this->saveObjects(
            4, function ($i) use ($prefixes, $suffixes, $someAscii) {
                $obj = ParseObject::create("TestObject");
                $obj->set("myString", $prefixes[$i].$someAscii.$suffixes[$i]);

                return $obj;
            }
        );
        $query = new ParseQuery("TestObject");
        $query->startsWith("myString", $someAscii);
        $results = $query->find();
        $this->assertEquals(
            2, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function provideTestObjectsForOrderBy()
    {
        ParseTestHelper::clearClass("BoxedNumber");
        $strings = ['a', 'b', 'c', 'd'];
        $numbers = [3, 1, 3, 2];
        for ($i = 0; $i < 4; $i++) {
            $obj = ParseObject::create("BoxedNumber");
            $obj->set('string', $strings[$i]);
            $obj->set('number', $numbers[$i]);
            $obj->save();
        }
    }

    public function testOrderByAscNumberThenDescString()
    {
        $this->provideTestObjectsForOrderBy();
        $query = new ParseQuery("BoxedNumber");
        $query->ascending('number')->addDescending('string');
        $results = $query->find();
        $expected = [[1, 'b'], [2, 'd'], [3, 'c'], [3, 'a']];
        $this->assertEquals(
            4, count($results),
            'Did not return correct number of objects.'
        );
        for ($i = 0; $i < 4; $i++) {
            $this->assertEquals(
                $expected[$i][0], $results[$i]->get('number'),
                'Did not return the correct object.'
            );
            $this->assertEquals(
                $expected[$i][1], $results[$i]->get('string'),
                'Did not return the correct object.'
            );
        }
    }

    public function testOrderByDescNumberThenAscString()
    {
        $this->provideTestObjectsForOrderBy();
        $query = new ParseQuery("BoxedNumber");
        $query->descending('number')->addAscending('string');
        $results = $query->find();
        $expected = [[3, 'a'], [3, 'c'], [2, 'd'], [1, 'b']];
        $this->assertEquals(
            4, count($results),
            'Did not return correct number of objects.'
        );
        for ($i = 0; $i < 4; $i++) {
            $this->assertEquals(
                $expected[$i][0], $results[$i]->get('number'),
                'Did not return the correct object.'
            );
            $this->assertEquals(
                $expected[$i][1], $results[$i]->get('string'),
                'Did not return the correct object.'
            );
        }
    }

    public function testOrderByDescNumberAndString()
    {
        $this->provideTestObjectsForOrderBy();
        $query = new ParseQuery("BoxedNumber");
        $query->descending(['number', 'string']);
        $results = $query->find();
        $expected = [[3, 'c'], [3, 'a'], [2, 'd'], [1, 'b']];
        $this->assertEquals(
            4, count($results),
            'Did not return correct number of objects.'
        );
        for ($i = 0; $i < 4; $i++) {
            $this->assertEquals(
                $expected[$i][0], $results[$i]->get('number'),
                'Did not return the correct object.'
            );
            $this->assertEquals(
                $expected[$i][1], $results[$i]->get('string'),
                'Did not return the correct object.'
            );
        }
    }

    public function testCannotOrderByPassword()
    {
        $this->provideTestObjectsForOrderBy();
        $query = new ParseQuery("BoxedNumber");
        $query->ascending('_password');
        $this->setExpectedException('Parse\ParseException', "", 105);
        $query->find();
    }

    public function testOrderByCreatedAtAsc()
    {
        $this->provideTestObjectsForOrderBy();
        $query = new ParseQuery("BoxedNumber");
        $query->ascending('createdAt');
        $query->find();
        $results = $query->find();
        $this->assertEquals(
            4, count($results),
            'Did not return correct number of objects.'
        );
        $expected = [3, 1, 3, 2];
        for ($i = 0; $i < 4; $i++) {
            $this->assertEquals(
                $expected[$i], $results[$i]->get('number'),
                'Did not return the correct object.'
            );
        }
    }

    public function testOrderByCreatedAtDesc()
    {
        $this->provideTestObjectsForOrderBy();
        $query = new ParseQuery("BoxedNumber");
        $query->descending('createdAt');
        $query->find();
        $results = $query->find();
        $this->assertEquals(
            4, count($results),
            'Did not return correct number of objects.'
        );
        $expected = [2, 3, 1, 3];
        for ($i = 0; $i < 4; $i++) {
            $this->assertEquals(
                $expected[$i], $results[$i]->get('number'),
                'Did not return the correct object.'
            );
        }
    }

    public function testOrderByUpdatedAtAsc()
    {
        $numbers = [3, 1, 2];
        $objects = [];
        $this->saveObjects(
            3, function ($i) use ($numbers, &$objects) {
                $obj = ParseObject::create("TestObject");
                $obj->set('number', $numbers[$i]);
                $objects[] = $obj;

                return $obj;
            }
        );
        $objects[1]->set('number', 4);
        $objects[1]->save();
        $query = new ParseQuery("TestObject");
        $query->ascending('updatedAt');
        $results = $query->find();
        $this->assertEquals(
            3, count($results),
            'Did not return correct number of objects.'
        );
        $expected = [3, 2, 4];
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals(
                $expected[$i], $results[$i]->get('number'),
                'Did not return the correct object.'
            );
        }
    }

    public function testOrderByUpdatedAtDesc()
    {
        $numbers = [3, 1, 2];
        $objects = [];
        $this->saveObjects(
            3, function ($i) use ($numbers, &$objects) {
                $obj = ParseObject::create("TestObject");
                $obj->set('number', $numbers[$i]);
                $objects[] = $obj;

                return $obj;
            }
        );
        $objects[1]->set('number', 4);
        $objects[1]->save();
        $query = new ParseQuery("TestObject");
        $query->descending('updatedAt');
        $results = $query->find();
        $this->assertEquals(
            3, count($results),
            'Did not return correct number of objects.'
        );
        $expected = [4, 2, 3];
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals(
                $expected[$i], $results[$i]->get('number'),
                'Did not return the correct object.'
            );
        }
    }

    public function testSelectKeysQuery()
    {
        $obj = ParseObject::create("TestObject");
        $obj->set('foo', 'baz');
        $obj->set('bar', 1);
        $obj->save();
        $query = new ParseQuery("TestObject");
        $query->select('foo');
        $result = $query->first();
        $this->assertEquals(
            'baz', $result->get('foo'),
            'Did not return the correct object.'
        );
        $this->setExpectedException('\Exception', 'Call fetch()');
        $result->get('bar');
    }

    public function testGetWithoutError()
    {
        $obj = ParseObject::create("TestObject");
        $obj->set('foo', 'baz');
        $obj->set('bar', 1);
        $this->assertEquals(
            'baz', $obj->get('foo'),
            'Did not return the correct object.'
        );
        $this->assertEquals(
            1, $obj->get('bar'),
            'Did not return the correct object.'
        );
        $obj->save();
    }
    public function testSelectKeysQueryArrayArg()
    {
        $obj = ParseObject::create("TestObject");
        $obj->set('foo', 'baz');
        $obj->set('bar', 1);
        $obj->save();
        $query = new ParseQuery("TestObject");
        $query->select(['foo', 'bar']);
        $result = $query->first();
        $this->assertEquals(
            'baz', $result->get('foo'),
            'Did not return the correct object.'
        );
        $this->assertEquals(
            1, $result->get('bar'),
            'Did not return the correct object.'
        );
    }

    public function testExists()
    {
        $this->saveObjects(
            9, function ($i) {
                $obj = ParseObject::create("TestObject");
                if ($i & 1) {
                    $obj->set('y', $i);
                } else {
                    $obj->set('x', $i);
                }

                return $obj;
            }
        );
        $query = new ParseQuery("TestObject");
        $query->exists('x');
        $results = $query->find();
        $this->assertEquals(
            5, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testDoesNotExist()
    {
        $this->saveObjects(
            9, function ($i) {
                $obj = ParseObject::create("TestObject");
                if ($i & 1) {
                    $obj->set('y', $i);
                } else {
                    $obj->set('x', $i);
                }

                return $obj;
            }
        );
        $query = new ParseQuery("TestObject");
        $query->doesNotExist('x');
        $results = $query->find();
        $this->assertEquals(
            4, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testExistsRelation()
    {
        ParseTestHelper::clearClass("Item");
        $this->saveObjects(
            9, function ($i) {
                $obj = ParseObject::create("TestObject");
                if ($i & 1) {
                    $obj->set('y', $i);
                } else {
                    $item = ParseObject::create("Item");
                    $item->set('e', $i);
                    $obj->set('e', $item);
                }

                return $obj;
            }
        );
        $query = new ParseQuery("TestObject");
        $query->exists('e');
        $results = $query->find();
        $this->assertEquals(
            5, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testDoesNotExistRelation()
    {
        ParseTestHelper::clearClass("Item");
        $this->saveObjects(
            9, function ($i) {
                $obj = ParseObject::create("TestObject");
                if ($i & 1) {
                    $obj->set('y', $i);
                } else {
                    $item = ParseObject::create("Item");
                    $item->set('x', $i);
                    $obj->set('x', $i);
                }

                return $obj;
            }
        );
        $query = new ParseQuery("TestObject");
        $query->doesNotExist('x');
        $results = $query->find();
        $this->assertEquals(
            4, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testDoNotIncludeRelation()
    {
        $child = ParseObject::create("Child");
        $child->set('x', 1);
        $child->save();
        $parent = ParseObject::create("Parent");
        $parent->set('child', $child);
        $parent->set('y', 1);
        $parent->save();
        $query = new ParseQuery('Parent');
        $result = $query->first();
        $this->setExpectedException('\Exception', 'Call fetch()');
        $result->get('child')->get('x');
    }

    public function testIncludeRelation()
    {
        ParseTestHelper::clearClass("Child");
        ParseTestHelper::clearClass("Parent");
        $child = ParseObject::create("Child");
        $child->set('x', 1);
        $child->save();
        $parent = ParseObject::create("Parent");
        $parent->set('child', $child);
        $parent->set('y', 1);
        $parent->save();
        $query = new ParseQuery('Parent');
        $query->includeKey('child');
        $result = $query->first();
        $this->assertEquals(
            $result->get('y'), $result->get('child')->get('x'),
            'Object should be fetched.'
        );
        $this->assertEquals(
            1, $result->get('child')->get('x'),
            'Object should be fetched.'
        );
    }

    public function testNestedIncludeRelation()
    {
        ParseTestHelper::clearClass("Child");
        ParseTestHelper::clearClass("Parent");
        ParseTestHelper::clearClass("GrandParent");
        $child = ParseObject::create("Child");
        $child->set('x', 1);
        $child->save();
        $parent = ParseObject::create("Parent");
        $parent->set('child', $child);
        $parent->set('y', 1);
        $parent->save();
        $grandParent = ParseObject::create("GrandParent");
        $grandParent->set('parent', $parent);
        $grandParent->set('z', 1);
        $grandParent->save();

        $query = new ParseQuery('GrandParent');
        $query->includeKey('parent.child');
        $result = $query->first();
        $this->assertEquals(
            $result->get('z'), $result->get('parent')->get('y'),
            'Object should be fetched.'
        );
        $this->assertEquals(
            $result->get('z'),
            $result->get('parent')->get('child')->get('x'),
            'Object should be fetched.'
        );
    }

    public function testIncludeArrayRelation()
    {
        ParseTestHelper::clearClass("Child");
        ParseTestHelper::clearClass("Parent");
        $children = [];
        $this->saveObjects(
            5, function ($i) use (&$children) {
                $child = ParseObject::create("Child");
                $child->set('x', $i);
                $children[] = $child;

                return $child;
            }
        );
        $parent = ParseObject::create("Parent");
        $parent->setArray('children', $children);
        $parent->save();

        $query = new ParseQuery("Parent");
        $query->includeKey('children');
        $result = $query->find();
        $this->assertEquals(
            1, count($result),
            'Did not return correct number of objects.'
        );
        $children = $result[0]->get('children');
        $length = count($children);
        for ($i = 0; $i < $length; $i++) {
            $this->assertEquals(
                $i, $children[$i]->get('x'),
                'Object should be fetched.'
            );
        }
    }

    public function testIncludeWithNoResults()
    {
        ParseTestHelper::clearClass("Child");
        ParseTestHelper::clearClass("Parent");
        $query = new ParseQuery("Parent");
        $query->includeKey('children');
        $result = $query->find();
        $this->assertEquals(
            0, count($result),
            'Did not return correct number of objects.'
        );
    }

    public function testIncludeWithNonExistentKey()
    {
        ParseTestHelper::clearClass("Child");
        ParseTestHelper::clearClass("Parent");
        $parent = ParseObject::create("Parent");
        $parent->set('foo', 'bar');
        $parent->save();

        $query = new ParseQuery("Parent");
        $query->includeKey('child');
        $results = $query->find();
        $this->assertEquals(
            1, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testIncludeOnTheWrongKeyType()
    {
        ParseTestHelper::clearClass("Child");
        ParseTestHelper::clearClass("Parent");
        $parent = ParseObject::create("Parent");
        $parent->set('foo', 'bar');
        $parent->save();

        $query = new ParseQuery("Parent");
        $query->includeKey('foo');
        $this->setExpectedException('Parse\ParseException', '', 102);
        $results = $query->find();
        $this->assertEquals(
            1, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testIncludeWhenOnlySomeObjectsHaveChildren()
    {
        ParseTestHelper::clearClass("Child");
        ParseTestHelper::clearClass("Parent");
        $child = ParseObject::create('Child');
        $child->set('foo', 'bar');
        $child->save();
        $this->saveObjects(
            4, function ($i) use ($child) {
                $parent = ParseObject::create('Parent');
                $parent->set('num', $i);
                if ($i & 1) {
                    $parent->set('child', $child);
                }

                return $parent;
            }
        );

        $query = new ParseQuery('Parent');
        $query->includeKey(['child']);
        $query->ascending('num');
        $results = $query->find();
        $this->assertEquals(
            4, count($results),
            'Did not return correct number of objects.'
        );
        $length = count($results);
        for ($i = 0; $i < $length; $i++) {
            if ($i & 1) {
                $this->assertEquals(
                    'bar', $results[$i]->get('child')->get('foo'),
                    'Object should be fetched'
                );
            } else {
                $this->assertEquals(
                    null, $results[$i]->get('child'),
                    'Should not have child'
                );
            }
        }
    }

    public function testIncludeMultipleKeys()
    {
        ParseTestHelper::clearClass("Foo");
        ParseTestHelper::clearClass("Bar");
        ParseTestHelper::clearClass("Parent");
        $foo = ParseObject::create('Foo');
        $foo->set('rev', 'oof');
        $foo->save();
        $bar = ParseObject::create('Bar');
        $bar->set('rev', 'rab');
        $bar->save();

        $parent = ParseObject::create('Parent');
        $parent->set('foofoo', $foo);
        $parent->set('barbar', $bar);
        $parent->save();

        $query = new ParseQuery('Parent');
        $query->includeKey(['foofoo', 'barbar']);
        $result = $query->first();
        $this->assertEquals(
            'oof', $result->get('foofoo')->get('rev'),
            'Object should be fetched'
        );
        $this->assertEquals(
            'rab', $result->get('barbar')->get('rev'),
            'Object should be fetched'
        );
    }

    public function testEqualToObject()
    {
        ParseTestHelper::clearClass("Item");
        ParseTestHelper::clearClass("Container");
        $items = [];
        $this->saveObjects(
            2, function ($i) use (&$items) {
                $items[] = ParseObject::create("Item");
                $items[$i]->set('x', $i);

                return $items[$i];
            }
        );
        $this->saveObjects(
            2, function ($i) use ($items) {
                $container = ParseObject::create("Container");
                $container->set('item', $items[$i]);

                return $container;
            }
        );
        $query = new ParseQuery("Container");
        $query->equalTo('item', $items[0]);
        $result = $query->find();
        $this->assertEquals(
            1, count($result),
            'Did not return the correct object.'
        );
    }

    public function testEqualToNull()
    {
        $this->saveObjects(
            10, function ($i) {
                $obj = ParseObject::create('TestObject');
                $obj->set('num', $i);

                return $obj;
            }
        );
        $query = new ParseQuery('TestObject');
        $query->equalTo('num', null);
        $results = $query->find();
        $this->assertEquals(
            0, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function provideTimeTestObjects()
    {
        ParseTestHelper::clearClass("TimeObject");
        $items = [];
        $this->saveObjects(
            3, function ($i) use (&$items) {
                $timeObject = ParseObject::create('TimeObject');
                $timeObject->set('name', 'item'.$i);
                $timeObject->set('time', new DateTime());
                sleep(1);
                $items[] = $timeObject;

                return $timeObject;
            }
        );

        return $items;
    }

    public function testTimeEquality()
    {
        $items = $this->provideTimeTestObjects();
        $query = new ParseQuery('TimeObject');
        $query->equalTo('time', $items[1]->get('time'));
        $results = $query->find();
        $this->assertEquals(
            1, count($results),
            'Did not return correct number of objects.'
        );
        $this->assertEquals('item1', $results[0]->get('name'));
    }

    public function testTimeLessThan()
    {
        $items = $this->provideTimeTestObjects();
        $query = new ParseQuery('TimeObject');
        $query->lessThan('time', $items[2]->get('time'));
        $results = $query->find();
        $this->assertEquals(
            2, count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testRestrictedGetFailsWithoutMasterKey()
    {
        $obj = ParseObject::create("TestObject");
        $restrictedACL = new ParseACL();
        $obj->setACL($restrictedACL);
        $obj->save();
        $query = new ParseQuery("TestObject");
        $this->setExpectedException('Parse\ParseException', 'not found');
        $objAgain = $query->get($obj->getObjectId());
    }

    public function testRestrictedGetWithMasterKey()
    {
        $obj = ParseObject::create("TestObject");
        $restrictedACL = new ParseACL();
        $obj->setACL($restrictedACL);
        $obj->save();

        $query = new ParseQuery("TestObject");
        $objAgain = $query->get($obj->getObjectId(), true);
        $this->assertEquals($obj->getObjectId(), $objAgain->getObjectId());
    }

    public function testRestrictedCount()
    {
        $obj = ParseObject::create("TestObject");
        $restrictedACL = new ParseACL();
        $obj->setACL($restrictedACL);
        $obj->save();

        $query = new ParseQuery("TestObject");
        $count = $query->count();
        $this->assertEquals(0, $count);
        $count = $query->count(true);
        $this->assertEquals(1, $count);
    }
}

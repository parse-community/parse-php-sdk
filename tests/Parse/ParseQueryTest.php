<?php

namespace Parse\Test;

use Parse\ParseACL;
use Parse\ParseException;
use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseUser;
use Parse\ParseClient;

use PHPUnit\Framework\TestCase;

class ParseQueryTest extends TestCase
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
     * This function used as a helper function in test functions to save objects.
     *
     * @param int      $numberOfObjects Number of objects you want to save.
     * @param callable $callback        Function which takes int as a parameter.
     *                                  and should return ParseObject.
     */
    public function saveObjects($numberOfObjects, $callback)
    {
        $allObjects = [];
        for ($i = 0; $i < $numberOfObjects; ++$i) {
            $allObjects[] = $callback($i);
        }
        ParseObject::saveAll($allObjects);
    }

    public function provideTestObjects($numberOfObjects)
    {
        $this->saveObjects(
            $numberOfObjects,
            function ($i) {
                $obj = ParseObject::create('TestObject');
                $obj->set('foo', 'bar'.$i);

                return $obj;
            }
        );
    }

    public function testBasicQuery()
    {
        $baz = new ParseObject('TestObject');
        $baz->set('foo', 'baz');
        $qux = new ParseObject('TestObject');
        $qux->set('foo', 'qux');
        $baz->save();
        $qux->save();

        $query = new ParseQuery('TestObject');
        $query->equalTo('foo', 'baz');
        $results = $query->find();
        $this->assertEquals(
            1,
            count($results),
            'Did not find object.'
        );
        $this->assertEquals(
            'baz',
            $results[0]->get('foo'),
            'Did not return the correct object.'
        );
    }

    public function testQueryWithLimit()
    {
        $baz = new ParseObject('TestObject');
        $baz->set('foo', 'baz');
        $qux = new ParseObject('TestObject');
        $qux->set('foo', 'qux');
        $baz->save();
        $qux->save();

        $query = new ParseQuery('TestObject');
        $query->limit(1);
        $results = $query->find();
        $this->assertEquals(
            1,
            count($results),
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
            count($results),
            9,
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
            count($results),
            1,
            'LessThan function did not return correct number of objects.'
        );
        $this->assertEquals(
            $results[0]->get('foo'),
            'bar0',
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
            count($results),
            1,
            'LessThanOrEqualTo function did not return correct number of objects.'
        );
        $this->assertEquals(
            $results[0]->get('foo'),
            'bar0',
            'LessThanOrEqualTo function did not return the correct object.'
        );
    }

    public function testEndsWithSingle()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->endsWith('foo', 'r0');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            1,
            'EndsWith function did not return correct number of objects.'
        );
        $this->assertEquals(
            $results[0]->get('foo'),
            'bar0',
            'EndsWith function did not return the correct object.'
        );
    }

    public function testStartsWithSingle()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->startsWith('foo', 'bar0');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            1,
            'StartsWith function did not return correct number of objects.'
        );
        $this->assertEquals(
            $results[0]->get('foo'),
            'bar0',
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
            count($results),
            10,
            'StartsWith function did not return correct number of objects.'
        );
    }

    public function testStartsWithMiddle()
    {

        $user = ParseUser::getCurrentUser();
        if (isset($user)) {
            // logout the current user
            ParseUser::logOut();
        }

        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->startsWith('foo', 'ar');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            0,
            'StartsWith function did not return correct number of objects.'
        );
    }

    public function testStartsWithRegexDelimiters()
    {
        $testObject = ParseObject::create('TestObject');
        $testObject->set('foo', "foob\E");
        $testObject->save();
        $query = new ParseQuery('TestObject');
        $query->startsWith('foo', 'foob\E');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            1,
            'StartsWith function did not return correct number of objects.'
        );
        $query->startsWith('foo', 'foobE');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            0,
            'StartsWith function did not return correct number of objects.'
        );
    }

    public function testStartsWithRegexDot()
    {
        $testObject = ParseObject::create('TestObject');
        $testObject->set('foo', 'foobarfoo');
        $testObject->save();
        $query = new ParseQuery('TestObject');
        $query->startsWith('foo', 'foo(.)*');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            0,
            'StartsWith function did not return correct number of objects.'
        );
        $query->startsWith('foo', 'foo.*');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            0,
            'StartsWith function did not return correct number of objects.'
        );
        $query->startsWith('foo', 'foo');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            1,
            'StartsWith function did not return correct number of objects.'
        );
    }

    public function testStartsWithRegexSlash()
    {
        $testObject = ParseObject::create('TestObject');
        $testObject->set('foo', 'foobarfoo');
        $testObject->save();
        $query = new ParseQuery('TestObject');
        $query->startsWith('foo', 'foo/bar');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            0,
            'StartsWith function did not return correct number of objects.'
        );
        $query->startsWith('foo', 'foobar');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            1,
            'StartsWith function did not return correct number of objects.'
        );
    }

    public function testStartsWithRegexQuestionmark()
    {
        $testObject = ParseObject::create('TestObject');
        $testObject->set('foo', 'foobarfoo');
        $testObject->save();
        $query = new ParseQuery('TestObject');
        $query->startsWith('foo', 'foox?bar');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            0,
            'StartsWith function did not return correct number of objects.'
        );
        $query->startsWith('foo', 'foo(x)?bar');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            0,
            'StartsWith function did not return correct number of objects.'
        );
        $query->startsWith('foo', 'foobar');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            1,
            'StartsWith function did not return correct number of objects.'
        );
    }

    public function testMatchesSingle()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->matches('foo', 'bar0');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            1,
            'Matches function did not return correct number of objects.'
        );
        $this->assertEquals(
            $results[0]->get('foo'),
            'bar0',
            'Matches function did not return the correct object.'
        );
    }

    public function testMatchesMultiple()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->matches('foo', 'bar');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            10,
            'Matches function did not return correct number of objects.'
        );
    }

    public function testMatchesRegexDelimiters()
    {
        $testObject = ParseObject::create('TestObject');
        $testObject->set('foo', "foob\E");
        $testObject->save();
        $query = new ParseQuery('TestObject');
        $query->matches('foo', 'foob\E');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            1,
            'Matches function did not return correct number of objects.'
        );
        $query->matches('foo', 'foobE');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            0,
            'Matches function did not return correct number of objects.'
        );
    }

    public function testMatchesCaseInsensitiveModifier()
    {
        $testObject = ParseObject::create('TestObject');
        $testObject->set('foo', 'FOOBAR');
        $testObject->save();
        $query = new ParseQuery('TestObject');
        $query->matches('foo', 'foo', 'i');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            1,
            'Matches function did not return correct number of objects.'
        );
        $this->assertEquals(
            $results[0]->get('foo'),
            'FOOBAR',
            'Matches function did not return correct number of objects.'
        );
    }

    public function testMatchesMultilineModifier()
    {
        $testObject = ParseObject::create('TestObject');
        $testObject->set('foo', 'foo\nbar');
        $testObject->save();
        $query = new ParseQuery('TestObject');
        $query->matches('foo', 'bar', 'm');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            1,
            'Matches function did not return correct number of objects.'
        );
    }

    public function testMatchesBadOptions()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->matches('foo', 'bar', 'not-a-real-modifier');
        $this->expectException('Parse\ParseException', 'Bad $options value for query: not-a-real-modifier', 102);
        $query->find();
    }

    public function testContainsSingle()
    {
        $testObject = ParseObject::create('TestObject');
        $testObject->set('foo', 'foobarfoo');
        $testObject->save();
        $query = new ParseQuery('TestObject');
        $query->contains('foo', 'bar');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            1,
            'Contains should find the string.'
        );
    }

    public function testContainsMultiple()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->contains('foo', 'bar');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            10,
            'Contains function did not return correct number of objects.'
        );

        $query = new ParseQuery('TestObject');
        $query->contains('foo', '8');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            1,
            'Contains function did not return correct number of objects.'
        );
    }

    public function testContainsNonExistent()
    {
        $testObject = ParseObject::create('TestObject');
        $testObject->set('foo', 'foobarfoo');
        $testObject->save();
        $query = new ParseQuery('TestObject');
        $query->contains('foo', 'baz');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            0,
            'Contains should not find.'
        );
    }

    public function testGreaterThan()
    {
        $this->provideTestObjects(10);
        $query = new ParseQuery('TestObject');
        $query->greaterThan('foo', 'bar8');
        $results = $query->find();
        $this->assertEquals(
            count($results),
            1,
            'GreaterThan function did not return correct number of objects.'
        );
        $this->assertEquals(
            $results[0]->get('foo'),
            'bar9',
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
            count($results),
            1,
            'GreaterThanOrEqualTo function did not return correct number of objects.'
        );
        $this->assertEquals(
            $results[0]->get('foo'),
            'bar9',
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
            3,
            count($results),
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
            1,
            count($results),
            'LessThanGreaterThan did not return correct number of objects.'
        );
        $this->assertEquals(
            'bar4',
            $results[0]->get('foo'),
            'LessThanGreaterThan did not return the correct object.'
        );
    }

    public function testObjectIdEqualTo()
    {
        Helper::clearClass('BoxedNumber');
        $boxedNumberArray = [];
        $this->saveObjects(
            5,
            function ($i) use (&$boxedNumberArray) {
                $boxedNumber = new ParseObject('BoxedNumber');
                $boxedNumber->set('number', $i);
                $boxedNumberArray[] = $boxedNumber;

                return $boxedNumber;
            }
        );
        $query = new ParseQuery('BoxedNumber');
        $query->equalTo('objectId', $boxedNumberArray[4]->getObjectId());
        $results = $query->find();
        $this->assertEquals(
            1,
            count($results),
            'Did not find object.'
        );
        $this->assertEquals(
            4,
            $results[0]->get('number'),
            'Did not return the correct object.'
        );
    }

    public function testFindNoElements()
    {
        Helper::clearClass('BoxedNumber');
        $this->saveObjects(
            5,
            function ($i) {
                $boxedNumber = new ParseObject('BoxedNumber');
                $boxedNumber->set('number', $i);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery('BoxedNumber');
        $query->equalTo('number', 17);
        $results = $query->find();
        $this->assertEquals(
            0,
            count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testFindWithError()
    {
        $query = new ParseQuery('TestObject');
        $this->expectException('Parse\ParseException', 'Invalid key name: $foo', 105);
        $query->equalTo('$foo', 'bar');
        $query->find();
    }

    public function testGet()
    {
        $testObj = ParseObject::create('TestObject');
        $testObj->set('foo', 'bar');
        $testObj->save();
        $query = new ParseQuery('TestObject');
        $result = $query->get($testObj->getObjectId());
        $this->assertEquals(
            $testObj->getObjectId(),
            $result->getObjectId(),
            'Did not return the correct object.'
        );
        $this->assertEquals(
            'bar',
            $result->get('foo'),
            'Did not return the correct object.'
        );
    }

    public function testGetError()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->save();
        $query = new ParseQuery('TestObject');
        $this->expectException('Parse\ParseException', 'Object not found', 101);
        $query->get('InvalidObjectID');
    }

    public function testGetNull()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->save();
        $query = new ParseQuery('TestObject');
        $this->expectException('Parse\ParseException', 'Object not found', 101);
        $query->get(null);
    }

    public function testFirst()
    {
        $testObject = ParseObject::create('TestObject');
        $testObject->set('foo', 'bar');
        $testObject->save();
        $query = new ParseQuery('TestObject');
        $query->equalTo('foo', 'bar');
        $result = $query->first();
        $this->assertEquals(
            'bar',
            $result->get('foo'),
            'Did not return the correct object.'
        );
    }

    public function testFirstWithError()
    {
        $query = new ParseQuery('TestObject');
        $query->equalTo('$foo', 'bar');
        $this->expectException('Parse\ParseException', 'Invalid key name: $foo', 105);
        $query->first();
    }

    public function testFirstNoResult()
    {
        $testObject = ParseObject::create('TestObject');
        $testObject->set('foo', 'bar');
        $testObject->save();
        $query = new ParseQuery('TestObject');
        $query->equalTo('foo', 'baz');
        $result = $query->first();
        $this->assertTrue(
            empty($result),
            'Did not return correct number of objects.'
        );
    }

    public function testFirstWithTwoResults()
    {
        $this->saveObjects(
            2,
            function () {
                $testObject = ParseObject::create('TestObject');
                $testObject->set('foo', 'bar');

                return $testObject;
            }
        );
        $query = new ParseQuery('TestObject');
        $query->equalTo('foo', 'bar');
        $result = $query->first();
        $this->assertEquals(
            'bar',
            $result->get('foo'),
            'Did not return the correct object.'
        );
    }

    public function testNotEqualToObject()
    {
        Helper::clearClass('Container');
        Helper::clearClass('Item');
        $items = [];
        $this->saveObjects(
            2,
            function ($i) use (&$items) {
                $items[] = ParseObject::create('Item');

                return $items[$i];
            }
        );
        $this->saveObjects(
            2,
            function ($i) use ($items) {
                $container = ParseObject::create('Container');
                $container->set('item', $items[$i]);

                return $container;
            }
        );
        $query = new ParseQuery('Container');
        $query->notEqualTo('item', $items[0]);
        $result = $query->find();
        $this->assertEquals(
            1,
            count($result),
            'Did not return the correct object.'
        );
    }

    public function testSkip()
    {
        $this->saveObjects(
            2,
            function () {
                return ParseObject::create('TestObject');
            }
        );
        $query = new ParseQuery('TestObject');
        $query->skip(1);
        $result = $query->find();
        $this->assertEquals(
            1,
            count($result),
            'Did not return the correct object.'
        );
        $query->skip(3);
        $result = $query->find();
        $this->assertEquals(
            0,
            count($result),
            'Did not return the correct object.'
        );
    }

    public function testSkipDoesNotAffectCount()
    {
        $this->saveObjects(
            2,
            function () {
                return ParseObject::create('TestObject');
            }
        );
        $query = new ParseQuery('TestObject');
        $count = $query->count();
        $this->assertEquals(
            2,
            $count,
            'Did not return correct number of objects.'
        );
        $query->skip(1);
        $this->assertEquals(
            2,
            $count,
            'Did not return correct number of objects.'
        );
        $query->skip(3);
        $this->assertEquals(
            2,
            $count,
            'Did not return correct number of objects.'
        );
    }

    public function testCount()
    {
        Helper::clearClass('BoxedNumber');
        $this->saveObjects(
            3,
            function ($i) {
                $boxedNumber = ParseObject::create('BoxedNumber');
                $boxedNumber->set('x', $i + 1);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery('BoxedNumber');
        $query->greaterThan('x', 1);
        $count = $query->count();
        $this->assertEquals(
            2,
            $count,
            'Did not return correct number of objects.'
        );
    }

    /**
     * @group withCount
     */
    public function testWithCount()
    {
        Helper::clearClass('BoxedNumber');
        $this->saveObjects(
            3,
            function ($i) {
                $boxedNumber = ParseObject::create('BoxedNumber');
                $boxedNumber->set('x', $i + 1);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery('BoxedNumber');
        $query->withCount();
        $response = $query->find();
        $this->assertEquals($response['count'], 3);
        $this->assertEquals(count($response['results']), 3);
    }

    /**
     * @group withCount
     */
    public function testWithCountDestructure()
    {
        Helper::clearClass('BoxedNumber');
        $this->saveObjects(
            3,
            function ($i) {
                $boxedNumber = ParseObject::create('BoxedNumber');
                $boxedNumber->set('x', $i + 1);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery('BoxedNumber');
        $query->withCount();
        ['count' => $count, 'results' => $results] = $query->find();
        $this->assertEquals($count, 3);
        $this->assertEquals(count($results), 3);
    }

    /**
     * @group withCount
     */
    public function testWithCountFalse()
    {
        Helper::clearClass('BoxedNumber');
        $this->saveObjects(
            3,
            function ($i) {
                $boxedNumber = ParseObject::create('BoxedNumber');
                $boxedNumber->set('x', $i + 1);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery('BoxedNumber');
        $query->withCount(false);
        $response = $query->find();
        $this->assertEquals(isset($response['count']), false);
        $this->assertEquals(count($response), 3);
    }

    /**
     * @group withCount
     */
    public function testWithCountEmptyClass()
    {
        Helper::clearClass('BoxedNumber');
        $query = new ParseQuery('BoxedNumber');
        $query->withCount();
        $response = $query->find();
        $this->assertEquals($response['count'], 0);
        $this->assertEquals(count($response['results']), 0);
    }

    /**
     * @group withCount
     */
    public function testWithCountAndLimit()
    {
        Helper::clearClass('BoxedNumber');
        $this->saveObjects(
            4,
            function ($i) {
                $boxedNumber = ParseObject::create('BoxedNumber');
                $boxedNumber->set('x', $i + 1);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery('BoxedNumber');
        $query->withCount();
        $query->limit(2);
        $response = $query->find();
        $this->assertEquals($response['count'], 4);
        $this->assertEquals(count($response['results']), 2);
    }

    /**
     * @group withCount
     */
    public function testWithCountAndSkip()
    {
        Helper::clearClass('BoxedNumber');
        $this->saveObjects(
            4,
            function ($i) {
                $boxedNumber = ParseObject::create('BoxedNumber');
                $boxedNumber->set('x', $i + 1);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery('BoxedNumber');
        $query->withCount();
        $query->skip(3);
        $response = $query->find();
        $this->assertEquals($response['count'], 4);
        $this->assertEquals(count($response['results']), 1);
    }

    public function testCountError()
    {
        $query = new ParseQuery('Test');
        $query->equalTo('$foo', 'bar');
        $this->expectException('Parse\ParseException', 'Invalid key name: $foo', 105);
        $query->count();
    }

    /**
     * @group query-equalTo-Zero-Count
     */
    public function testEqualToCountZero()
    {
        Helper::clearClass('BoxedNumber');
        $this->saveObjects(
            5,
            function ($i) {
                $boxedNumber = new ParseObject('BoxedNumber');
                $boxedNumber->set('Number', 0);
                return $boxedNumber;
            }
        );
        $query = new ParseQuery('BoxedNumber');
        $query->equalTo('Number', 0);
        $result = $query->count();
        $this->assertEquals(
            5,
            $result,
            'Did not return correct number of objects.'
        );
    }

    public function testOrderByAscendingNumber()
    {
        Helper::clearClass('BoxedNumber');
        $numbers = [3, 1, 2];
        $this->saveObjects(
            3,
            function ($i) use ($numbers) {
                $boxedNumber = ParseObject::create('BoxedNumber');
                $boxedNumber->set('number', $numbers[$i]);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery('BoxedNumber');
        $query->ascending('number');
        $results = $query->find();
        $this->assertEquals(
            3,
            count($results),
            'Did not return correct number of objects.'
        );
        for ($i = 0; $i < 3; ++$i) {
            $this->assertEquals(
                $i + 1,
                $results[$i]->get('number'),
                'Did not return the correct object.'
            );
        }
    }

    public function testOrderByDescendingNumber()
    {
        Helper::clearClass('BoxedNumber');
        $numbers = [3, 1, 2];
        $this->saveObjects(
            3,
            function ($i) use ($numbers) {
                $boxedNumber = ParseObject::create('BoxedNumber');
                $boxedNumber->set('number', $numbers[$i]);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery('BoxedNumber');
        $query->descending('number');
        $results = $query->find();
        $this->assertEquals(
            3,
            count($results),
            'Did not return correct number of objects.'
        );
        for ($i = 0; $i < 3; ++$i) {
            $this->assertEquals(
                3 - $i,
                $results[$i]->get('number'),
                'Did not return the correct object.'
            );
        }
    }

    public function provideTestObjectsForQuery($numberOfObjects)
    {
        $this->saveObjects(
            $numberOfObjects,
            function ($i) {
                $parent = ParseObject::create('ParentObject');
                $child = ParseObject::create('ChildObject');
                $child->set('x', $i);
                $parent->set('x', 10 + $i);
                $parent->set('child', $child);

                return $parent;
            }
        );
    }

    public function testMatchesQuery()
    {
        Helper::clearClass('ChildObject');
        Helper::clearClass('ParentObject');
        $this->provideTestObjectsForQuery(10);
        $subQuery = new ParseQuery('ChildObject');
        $subQuery->greaterThan('x', 5);
        $query = new ParseQuery('ParentObject');
        $query->matchesQuery('child', $subQuery);
        $results = $query->find();

        $this->assertEquals(
            4,
            count($results),
            'Did not return correct number of objects.'
        );
        foreach ($results as $parentObj) {
            $this->assertGreaterThan(
                15,
                $parentObj->get('x'),
                'Did not return the correct object.'
            );
        }
    }

    public function testDoesNotMatchQuery()
    {
        Helper::clearClass('ChildObject');
        Helper::clearClass('ParentObject');
        $this->provideTestObjectsForQuery(10);
        $subQuery = new ParseQuery('ChildObject');
        $subQuery->greaterThan('x', 5);
        $query = new ParseQuery('ParentObject');
        $query->doesNotMatchQuery('child', $subQuery);
        $results = $query->find();

        $this->assertEquals(
            6,
            count($results),
            'Did not return the correct object.'
        );
        foreach ($results as $parentObj) {
            $this->assertLessThanOrEqual(
                15,
                $parentObj->get('x'),
                'Did not return the correct object.'
            );
            $this->assertGreaterThanOrEqual(
                10,
                $parentObj->get('x'),
                'Did not return the correct object.'
            );
        }
    }

    public function provideTestObjectsForKeyInQuery()
    {
        Helper::clearClass('Restaurant');
        Helper::clearClass('Person');
        $restaurantLocations = ['Djibouti', 'Ouagadougou'];
        $restaurantRatings = [5, 3];
        $numberOFRestaurantObjects = count($restaurantLocations);

        $personHomeTown = ['Djibouti', 'Ouagadougou', 'Detroit'];
        $personName = ['Bob', 'Tom', 'Billy'];
        $numberOfPersonObjects = count($personHomeTown);

        $this->saveObjects(
            $numberOFRestaurantObjects,
            function ($i) use ($restaurantRatings, $restaurantLocations) {
                $restaurant = ParseObject::create('Restaurant');
                $restaurant->set('ratings', $restaurantRatings[$i]);
                $restaurant->set('location', $restaurantLocations[$i]);

                return $restaurant;
            }
        );

        $this->saveObjects(
            $numberOfPersonObjects,
            function ($i) use ($personHomeTown, $personName) {
                $person = ParseObject::create('Person');
                $person->set('hometown', $personHomeTown[$i]);
                $person->set('name', $personName[$i]);

                return $person;
            }
        );
    }

    public function testMatchesKeyInQuery()
    {
        $this->provideTestObjectsForKeyInQuery();
        $subQuery = new ParseQuery('Restaurant');
        $subQuery->greaterThan('ratings', 4);

        $query = new ParseQuery('Person');
        $query->matchesKeyInQuery('hometown', 'location', $subQuery);
        $results = $query->find();

        $this->assertEquals(
            1,
            count($results),
            'Did not return correct number of objects.'
        );
        $this->assertEquals(
            'Bob',
            $results[0]->get('name'),
            'Did not return the correct object.'
        );
    }

    public function testDoesNotMatchKeyInQuery()
    {
        $this->provideTestObjectsForKeyInQuery();
        $subQuery = new ParseQuery('Restaurant');
        $subQuery->greaterThanOrEqualTo('ratings', 3);

        $query = new ParseQuery('Person');
        $query->doesNotmatchKeyInQuery('hometown', 'location', $subQuery);
        $results = $query->find();

        $this->assertEquals(
            1,
            count($results),
            'Did not return correct number of objects.'
        );
        $this->assertEquals(
            'Billy',
            $results[0]->get('name'),
            'Did not return the correct object.'
        );
    }

    public function testOrQueries()
    {
        $this->provideTestObjects(10);
        $subQuery1 = new ParseQuery('TestObject');
        $subQuery1->lessThan('foo', 'bar2');
        $subQuery2 = new ParseQuery('TestObject');
        $subQuery2->greaterThan('foo', 'bar5');

        $mainQuery = ParseQuery::orQueries([$subQuery1, $subQuery2]);
        $results = $mainQuery->find();
        $length = count($results);
        $this->assertEquals(
            6,
            $length,
            'Did not return correct number of objects.'
        );
        for ($i = 0; $i < $length; ++$i) {
            $this->assertTrue(
                $results[$i]->get('foo') < 'bar2' ||
                $results[$i]->get('foo') > 'bar5',
                'Did not return the correct object.'
            );
        }
    }

    public function testNorQueries()
    {
        $this->provideTestObjects(10);
        $subQuery1 = new ParseQuery('TestObject');
        $subQuery1->lessThan('foo', 'bar3');
        $subQuery2 = new ParseQuery('TestObject');
        $subQuery2->greaterThan('foo', 'bar5');

        $mainQuery = ParseQuery::norQueries([$subQuery1, $subQuery2]);
        $results = $mainQuery->find();
        $length = count($results);
        $this->assertEquals(
            3,
            $length,
            'Did not return correct number of objects.'
        );
        for ($i = 0; $i < $length; ++$i) {
            $this->assertTrue(
                $results[$i]->get('foo') >= 'bar3' ||
                $results[$i]->get('foo') <= 'bar5',
                'Did not return the correct object.'
            );
        }
    }

    public function testAndQueries()
    {
        Helper::clearClass('ChildObject');
        Helper::clearClass('ParentObject');
        $this->provideTestObjectsForQuery(10);
        $subQuery = new ParseQuery('ChildObject');
        $subQuery->equalTo('x', 4);
        $q1 = new ParseQuery('ParentObject');
        $q1->matchesQuery('child', $subQuery);
        $q2 = new ParseQuery('ParentObject');
        $q2->equalTo('x', 14);

        $mainQuery = ParseQuery::andQueries([$q1, $q2]);
        $results = $mainQuery->find();
        $length = count($results);
        $this->assertEquals(
            1,
            $length,
            'Did not return correct number of objects.'
        );
        $this->assertTrue($results[0]->get('x') == 14);
    }

    public function testComplexQueries()
    {
        Helper::clearClass('Child');
        Helper::clearClass('Parent');
        $this->saveObjects(
            10,
            function ($i) {
                $child = new ParseObject('Child');
                $child->set('x', $i);
                $parent = new ParseObject('Parent');
                $parent->set('y', $i);
                $parent->set('child', $child);

                return $parent;
            }
        );
        $subQuery = new ParseQuery('Child');
        $subQuery->equalTo('x', 4);
        $query1 = new ParseQuery('Parent');
        $query1->matchesQuery('child', $subQuery);
        $query2 = new ParseQuery('Parent');
        $query2->lessThan('y', 2);

        $orQuery = ParseQuery::orQueries([$query1, $query2]);
        $results = $orQuery->find();
        $this->assertEquals(
            3,
            count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testEach()
    {
        Helper::clearClass('Object');
        $total = 50;
        $count = 25;
        $this->saveObjects(
            $total,
            function ($i) {
                $obj = new ParseObject('Object');
                $obj->set('x', $i + 1);

                return $obj;
            }
        );
        $query = new ParseQuery('Object');
        $query->lessThanOrEqualTo('x', $count);

        $values = [];
        $query->each(
            function ($obj) use (&$values) {
                $values[] = $obj->get('x');
            },
            10
        );

        $valuesLength = count($values);
        $this->assertEquals(
            $count,
            $valuesLength,
            'Did not return correct number of objects.'
        );
        sort($values);
        for ($i = 0; $i < $valuesLength; ++$i) {
            $this->assertEquals(
                $i + 1,
                $values[$i],
                'Did not return the correct object.'
            );
        }
    }

    public function testEachFailsWithOrder()
    {
        Helper::clearClass('Object');
        $total = 50;
        $count = 25;
        $this->saveObjects(
            $total,
            function ($i) {
                $obj = new ParseObject('Object');
                $obj->set('x', $i + 1);

                return $obj;
            }
        );
        $query = new ParseQuery('Object');
        $query->lessThanOrEqualTo('x', $count);
        $query->ascending('x');
        $this->expectException('\Exception', 'sort');
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
            $total,
            function ($i) {
                $obj = new ParseObject('Object');
                $obj->set('x', $i + 1);

                return $obj;
            }
        );
        $query = new ParseQuery('Object');
        $query->lessThanOrEqualTo('x', $count);
        $query->skip(5);
        $this->expectException('\Exception', 'skip');
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
            $total,
            function ($i) {
                $obj = new ParseObject('Object');
                $obj->set('x', $i + 1);

                return $obj;
            }
        );
        $query = new ParseQuery('Object');
        $query->lessThanOrEqualTo('x', $count);
        $query->limit(5);
        $this->expectException('\Exception', 'limit');
        $query->each(
            function ($obj) {
            }
        );
    }

    public function testContainsAllNumberArrayQueries()
    {
        Helper::clearClass('NumberSet');
        $numberSet1 = new ParseObject('NumberSet');
        $numberSet1->setArray('numbers', [1, 2, 3, 4, 5]);
        $numberSet2 = new ParseObject('NumberSet');
        $numberSet2->setArray('numbers', [1, 3, 4, 5]);
        $numberSet1->save();
        $numberSet2->save();

        $query = new ParseQuery('NumberSet');
        $query->containsAll('numbers', [1, 2, 3]);
        $results = $query->find();
        $this->assertEquals(
            1,
            count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testContainsAllStringArrayQueries()
    {
        Helper::clearClass('StringSet');
        $stringSet1 = new ParseObject('StringSet');
        $stringSet1->setArray('strings', ['a', 'b', 'c', 'd', 'e']);
        $stringSet1->save();
        $stringSet2 = new ParseObject('StringSet');
        $stringSet2->setArray('strings', ['a', 'c', 'd', 'e']);
        $stringSet2->save();

        $query = new ParseQuery('StringSet');
        $query->containsAll('strings', ['a', 'b', 'c']);
        $results = $query->find();
        $this->assertEquals(
            1,
            count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testContainsAllDateArrayQueries()
    {
        Helper::clearClass('DateSet');
        $dates1 = [
                new \DateTime('2013-02-01T00:00:00Z'),
                new \DateTime('2013-02-02T00:00:00Z'),
                new \DateTime('2013-02-03T00:00:00Z'),
                new \DateTime('2013-02-04T00:00:00Z'),
        ];
        $dates2 = [
                new \DateTime('2013-02-01T00:00:00Z'),
                new \DateTime('2013-02-03T00:00:00Z'),
                new \DateTime('2013-02-04T00:00:00Z'),
        ];

        $obj1 = ParseObject::create('DateSet');
        $obj1->setArray('dates', $dates1);
        $obj1->save();
        $obj2 = ParseObject::create('DateSet');
        $obj2->setArray('dates', $dates2);
        $obj2->save();

        $query = new ParseQuery('DateSet');
        $query->containsAll(
            'dates',
            [
                new \DateTime('2013-02-01T00:00:00Z'),
                new \DateTime('2013-02-02T00:00:00Z'),
                new \DateTime('2013-02-03T00:00:00Z'),
            ]
        );
        $result = $query->find();
        $this->assertEquals(
            1,
            count($result),
            'Did not return correct number of objects.'
        );
    }

    public function testContainedByQuery()
    {
        Helper::clearClass('NumberSet');
        $obj1 = ParseObject::create('TestObject');
        $obj2 = ParseObject::create('TestObject');
        $obj3 = ParseObject::create('TestObject');
        $obj1->setArray('numbers', [0, 1, 2]);
        $obj2->setArray('numbers', [2, 0]);
        $obj3->setArray('numbers', [1, 2, 3, 4]);
        $numberSet = [$obj1, $obj2, $obj3];

        ParseObject::saveAll($numberSet);

        $query = new ParseQuery('TestObject');
        $query->containedBy('numbers', [1, 2, 3, 4, 5]);
        $results = $query->find();
        $this->assertEquals(
            1,
            count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testContainsAllObjectArrayQueries()
    {
        Helper::clearClass('MessageSet');
        $messageList = [];
        $this->saveObjects(
            4,
            function ($i) use (&$messageList) {
                $messageList[] = ParseObject::create('TestObject');
                $messageList[$i]->set('i', $i);

                return $messageList[$i];
            }
        );
        $messageSet1 = ParseObject::create('MessageSet');
        $messageSet1->setArray('messages', $messageList);
        $messageSet1->save();
        $messageSet2 = ParseObject::create('MessageSet');
        $messageSet2->setArray(
            'message',
            [$messageList[0], $messageList[1], $messageList[3]]
        );
        $messageSet2->save();

        $query = new ParseQuery('MessageSet');
        $query->containsAll('messages', [$messageList[0], $messageList[2]]);
        $results = $query->find();
        $this->assertEquals(
            1,
            count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testContainsAllStartingWithQueries()
    {
        $obj1 = ParseObject::create('TestObject');
        $obj2 = ParseObject::create('TestObject');
        $obj3 = ParseObject::create('TestObject');
        $obj1->setArray('strings', ['the', 'brown', 'lazy', 'fox', 'jumps']);
        $obj2->setArray('strings', ['the', 'brown', 'fox', 'jumps']);
        $obj3->setArray('strings', ['over', 'the', 'lazy', 'dogs']);

        ParseObject::saveAll([$obj1, $obj2, $obj3]);

        $query = new ParseQuery('TestObject');
        $query->containsAllStartingWith('strings', ['the', 'fox', 'lazy']);
        $results = $query->find();
        $this->assertEquals(
            1,
            count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testContainedInObjectArrayQueries()
    {
        $messageList = [];
        $this->saveObjects(
            4,
            function ($i) use (&$messageList) {
                $message = ParseObject::create('TestObject');
                if ($i > 0) {
                    $message->set('prior', $messageList[$i - 1]);
                }
                $messageList[] = $message;

                return $message;
            }
        );
        $query = new ParseQuery('TestObject');
        $query->containedIn('prior', [$messageList[0], $messageList[2]]);
        $results = $query->find();
        $this->assertEquals(
            2,
            count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testContainedInQueries()
    {
        Helper::clearClass('BoxedNumber');
        $this->saveObjects(
            10,
            function ($i) {
                $boxedNumber = ParseObject::create('BoxedNumber');
                $boxedNumber->set('number', $i);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery('BoxedNumber');
        $query->containedIn('number', [3, 5, 7, 9, 11]);
        $results = $query->find();
        $this->assertEquals(
            4,
            count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testNotContainedInQueries()
    {
        Helper::clearClass('BoxedNumber');
        $this->saveObjects(
            10,
            function ($i) {
                $boxedNumber = ParseObject::create('BoxedNumber');
                $boxedNumber->set('number', $i);

                return $boxedNumber;
            }
        );
        $query = new ParseQuery('BoxedNumber');
        $query->notContainedIn('number', [3, 5, 7, 9, 11]);
        $results = $query->find();
        $this->assertEquals(
            6,
            count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testObjectIdContainedInQueries()
    {
        Helper::clearClass('BoxedNumber');
        $objects = [];
        $this->saveObjects(
            5,
            function ($i) use (&$objects) {
                $boxedNumber = ParseObject::create('BoxedNumber');
                $boxedNumber->set('number', $i);
                $objects[] = $boxedNumber;

                return $boxedNumber;
            }
        );
        $query = new ParseQuery('BoxedNumber');
        $query->containedIn(
            'objectId',
            [
                $objects[2]->getObjectId(),
                $objects[3]->getObjectId(),
                $objects[0]->getObjectId(),
                'NONSENSE',
            ]
        );
        $query->ascending('number');
        $results = $query->find();
        $this->assertEquals(
            3,
            count($results),
            'Did not return correct number of objects.'
        );
        $this->assertEquals(
            0,
            $results[0]->get('number'),
            'Did not return the correct object.'
        );
        $this->assertEquals(
            2,
            $results[1]->get('number'),
            'Did not return the correct object.'
        );
        $this->assertEquals(
            3,
            $results[2]->get('number'),
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
            4,
            function ($i) use ($prefixes, $suffixes, $someAscii) {
                $obj = ParseObject::create('TestObject');
                $obj->set('myString', $prefixes[$i].$someAscii.$suffixes[$i]);

                return $obj;
            }
        );
        $query = new ParseQuery('TestObject');
        $query->startsWith('myString', $someAscii);
        $results = $query->find();
        $this->assertEquals(
            2,
            count($results),
            'Did not return correct number of objects.'
        );
    }

    public function provideTestObjectsForOrderBy()
    {
        Helper::clearClass('BoxedNumber');
        $strings = ['a', 'b', 'c', 'd'];
        $numbers = [3, 1, 3, 2];
        for ($i = 0; $i < 4; ++$i) {
            $obj = ParseObject::create('BoxedNumber');
            $obj->set('string', $strings[$i]);
            $obj->set('number', $numbers[$i]);
            $obj->save();
        }
    }

    public function testOrderByAscNumberThenDescString()
    {
        $this->provideTestObjectsForOrderBy();
        $query = new ParseQuery('BoxedNumber');
        $query->ascending('number')->addDescending('string');
        $results = $query->find();
        $expected = [[1, 'b'], [2, 'd'], [3, 'c'], [3, 'a']];
        $this->assertEquals(
            4,
            count($results),
            'Did not return correct number of objects.'
        );
        for ($i = 0; $i < 4; ++$i) {
            $this->assertEquals(
                $expected[$i][0],
                $results[$i]->get('number'),
                'Did not return the correct object.'
            );
            $this->assertEquals(
                $expected[$i][1],
                $results[$i]->get('string'),
                'Did not return the correct object.'
            );
        }
    }

    public function testOrderByDescNumberThenAscString()
    {
        $this->provideTestObjectsForOrderBy();
        $query = new ParseQuery('BoxedNumber');
        $query->descending('number')->addAscending('string');
        $results = $query->find();
        $expected = [[3, 'a'], [3, 'c'], [2, 'd'], [1, 'b']];
        $this->assertEquals(
            4,
            count($results),
            'Did not return correct number of objects.'
        );
        for ($i = 0; $i < 4; ++$i) {
            $this->assertEquals(
                $expected[$i][0],
                $results[$i]->get('number'),
                'Did not return the correct object.'
            );
            $this->assertEquals(
                $expected[$i][1],
                $results[$i]->get('string'),
                'Did not return the correct object.'
            );
        }
    }

    public function testOrderByDescNumberAndString()
    {
        $this->provideTestObjectsForOrderBy();
        $query = new ParseQuery('BoxedNumber');
        $query->descending(['number', 'string']);
        $results = $query->find();
        $expected = [[3, 'c'], [3, 'a'], [2, 'd'], [1, 'b']];
        $this->assertEquals(
            4,
            count($results),
            'Did not return correct number of objects.'
        );
        for ($i = 0; $i < 4; ++$i) {
            $this->assertEquals(
                $expected[$i][0],
                $results[$i]->get('number'),
                'Did not return the correct object.'
            );
            $this->assertEquals(
                $expected[$i][1],
                $results[$i]->get('string'),
                'Did not return the correct object.'
            );
        }
    }

    public function testCannotOrderByPassword()
    {
        $this->provideTestObjectsForOrderBy();
        $query = new ParseQuery('BoxedNumber');
        $query->ascending('_password');
        $this->expectException('Parse\ParseException', '', 105);
        $query->find();
    }

    public function testOrderByCreatedAtAsc()
    {
        $this->provideTestObjectsForOrderBy();
        $query = new ParseQuery('BoxedNumber');
        $query->ascending('createdAt');
        $query->find();
        $results = $query->find();
        $this->assertEquals(
            4,
            count($results),
            'Did not return correct number of objects.'
        );
        $expected = [3, 1, 3, 2];
        for ($i = 0; $i < 4; ++$i) {
            $this->assertEquals(
                $expected[$i],
                $results[$i]->get('number'),
                'Did not return the correct object.'
            );
        }
    }

    public function testOrderByCreatedAtDesc()
    {
        $this->provideTestObjectsForOrderBy();
        $query = new ParseQuery('BoxedNumber');
        $query->descending('createdAt');
        $query->find();
        $results = $query->find();
        $this->assertEquals(
            4,
            count($results),
            'Did not return correct number of objects.'
        );
        $expected = [2, 3, 1, 3];
        for ($i = 0; $i < 4; ++$i) {
            $this->assertEquals(
                $expected[$i],
                $results[$i]->get('number'),
                'Did not return the correct object.'
            );
        }
    }

    /**
     * @group order-by-updated-at
     */
    public function testOrderByUpdatedAtAsc()
    {
        $numbers = [3, 1, 2];
        $objects = [];

        foreach ($numbers as $num) {
            $obj = ParseObject::create('TestObject');
            $obj->set('number', $num);
            $obj->save();
            $objects[]  = $obj;
            sleep(1);
        }

        $objects[1]->set('number', 4);
        $objects[1]->save();
        $query = new ParseQuery('TestObject');
        $query->ascending('updatedAt');
        $results = $query->find();
        $this->assertEquals(
            3,
            count($results),
            'Did not return correct number of objects.'
        );
        $expected = [3, 2, 4];
        for ($i = 0; $i < 3; ++$i) {
            $this->assertEquals(
                $expected[$i],
                $results[$i]->get('number'),
                'Did not return the correct object.'
            );
        }
    }

    /**
     * @throws ParseException
     * @group order-by-updated-at-desc
     */
    public function testOrderByUpdatedAtDesc()
    {
        $numbers = [3, 1, 2];
        $objects = [];

        foreach ($numbers as $num) {
            $obj = ParseObject::create('TestObject');
            $obj->set('number', $num);
            $obj->save();
            $objects[]  = $obj;
            sleep(1);
        }

        $objects[1]->set('number', 4);
        $objects[1]->save();
        $query = new ParseQuery('TestObject');
        $query->descending('updatedAt');
        $results = $query->find();
        $this->assertEquals(
            3,
            count($results),
            'Did not return correct number of objects.'
        );

        $expected = [4, 2, 3];
        for ($i = 0; $i < 3; ++$i) {
            $this->assertEquals(
                $expected[$i],
                $results[$i]->get('number'),
                'Did not return the correct object.'
            );
        }
    }

    public function testSelectKeysQuery()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'baz');
        $obj->set('bar', 1);
        $obj->save();
        $query = new ParseQuery('TestObject');
        $query->select('foo');
        $result = $query->first();
        $this->assertEquals(
            'baz',
            $result->get('foo'),
            'Did not return the correct object.'
        );
        $this->expectException('\Exception', 'Call fetch()');
        $result->get('bar');
    }

    public function testGetWithoutError()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'baz');
        $obj->set('bar', 1);
        $this->assertEquals(
            'baz',
            $obj->get('foo'),
            'Did not return the correct object.'
        );
        $this->assertEquals(
            1,
            $obj->get('bar'),
            'Did not return the correct object.'
        );
        $obj->save();
    }

    public function testSelectKeysQueryArrayArg()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'baz');
        $obj->set('bar', 1);
        $obj->save();
        $query = new ParseQuery('TestObject');
        $query->select(['foo', 'bar']);
        $result = $query->first();
        $this->assertEquals(
            'baz',
            $result->get('foo'),
            'Did not return the correct object.'
        );
        $this->assertEquals(
            1,
            $result->get('bar'),
            'Did not return the correct object.'
        );
    }

    public function testExists()
    {
        $this->saveObjects(
            9,
            function ($i) {
                $obj = ParseObject::create('TestObject');
                if ($i & 1) {
                    $obj->set('y', $i);
                } else {
                    $obj->set('x', $i);
                }

                return $obj;
            }
        );
        $query = new ParseQuery('TestObject');
        $query->exists('x');
        $results = $query->find();
        $this->assertEquals(
            5,
            count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testDoesNotExist()
    {
        $this->saveObjects(
            9,
            function ($i) {
                $obj = ParseObject::create('TestObject');
                if ($i & 1) {
                    $obj->set('y', $i);
                } else {
                    $obj->set('x', $i);
                }

                return $obj;
            }
        );
        $query = new ParseQuery('TestObject');
        $query->doesNotExist('x');
        $results = $query->find();
        $this->assertEquals(
            4,
            count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testExistsRelation()
    {
        Helper::clearClass('Item');
        $this->saveObjects(
            9,
            function ($i) {
                $obj = ParseObject::create('TestObject');
                if ($i & 1) {
                    $obj->set('y', $i);
                } else {
                    $item = ParseObject::create('Item');
                    $item->set('e', $i);
                    $obj->set('e', $item);
                }

                return $obj;
            }
        );
        $query = new ParseQuery('TestObject');
        $query->exists('e');
        $results = $query->find();
        $this->assertEquals(
            5,
            count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testDoesNotExistRelation()
    {
        Helper::clearClass('Item');
        $this->saveObjects(
            9,
            function ($i) {
                $obj = ParseObject::create('TestObject');
                if ($i & 1) {
                    $obj->set('y', $i);
                } else {
                    $item = ParseObject::create('Item');
                    $item->set('x', $i);
                    $obj->set('x', $i);
                }

                return $obj;
            }
        );
        $query = new ParseQuery('TestObject');
        $query->doesNotExist('x');
        $results = $query->find();
        $this->assertEquals(
            4,
            count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testDoNotIncludeRelation()
    {
        $child = ParseObject::create('Child');
        $child->set('x', 1);
        $child->save();
        $parent = ParseObject::create('Parent');
        $parent->set('child', $child);
        $parent->set('y', 1);
        $parent->save();
        $query = new ParseQuery('Parent');
        $result = $query->first();
        $this->expectException('\Exception', 'Call fetch()');
        $result->get('child')->get('x');
    }

    public function testIncludeRelation()
    {
        Helper::clearClass('Child');
        Helper::clearClass('Parent');
        $child = ParseObject::create('Child');
        $child->set('x', 1);
        $child->save();
        $parent = ParseObject::create('Parent');
        $parent->set('child', $child);
        $parent->set('y', 1);
        $parent->save();
        $query = new ParseQuery('Parent');
        $query->includeKey('child');
        $result = $query->first();
        $this->assertEquals(
            $result->get('y'),
            $result->get('child')->get('x'),
            'Object should be fetched.'
        );
        $this->assertEquals(
            1,
            $result->get('child')->get('x'),
            'Object should be fetched.'
        );
    }

    public function testIncludeAllKeys()
    {
        Helper::clearClass('Child');
        Helper::clearClass('Parent');
        $child1 = ParseObject::create('Child');
        $child2 = ParseObject::create('Child');
        $child3 = ParseObject::create('Child');
        $child1->set('foo', 'bar');
        $child2->set('foo', 'baz');
        $child3->set('foo', 'bin');
        $parent = ParseObject::create('Parent');
        $parent->set('child1', $child1);
        $parent->set('child2', $child2);
        $parent->set('child3', $child3);
        $parent->save();
        $query = new ParseQuery('Parent');
        $query->includeAllKeys();
        $result = $query->first();
        $this->assertEquals($result->get('child1')->get('foo'), 'bar');
        $this->assertEquals($result->get('child2')->get('foo'), 'baz');
        $this->assertEquals($result->get('child3')->get('foo'), 'bin');
    }

    public function testExcludeKeys()
    {
        $object = ParseObject::create('TestObject');
        $object->set('foo', 'bar');
        $object->set('hello', 'world');
        $object->save();
        $query = new ParseQuery('TestObject');
        $query->excludeKey('foo');
        $result = $query->get($object->getObjectId());
        $this->assertEquals($result->get('foo'), null);
        $this->assertEquals($result->get('hello'), 'world');
    }

    public function testNestedIncludeRelation()
    {
        Helper::clearClass('Child');
        Helper::clearClass('Parent');
        Helper::clearClass('GrandParent');
        $child = ParseObject::create('Child');
        $child->set('x', 1);
        $child->save();
        $parent = ParseObject::create('Parent');
        $parent->set('child', $child);
        $parent->set('y', 1);
        $parent->save();
        $grandParent = ParseObject::create('GrandParent');
        $grandParent->set('parent', $parent);
        $grandParent->set('z', 1);
        $grandParent->save();

        $query = new ParseQuery('GrandParent');
        $query->includeKey('parent.child');
        $result = $query->first();
        $this->assertEquals(
            $result->get('z'),
            $result->get('parent')->get('y'),
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
        Helper::clearClass('Child');
        Helper::clearClass('Parent');
        $children = [];
        $this->saveObjects(
            5,
            function ($i) use (&$children) {
                $child = ParseObject::create('Child');
                $child->set('x', $i);
                $children[] = $child;

                return $child;
            }
        );
        $parent = ParseObject::create('Parent');
        $parent->setArray('children', $children);
        $parent->save();

        $query = new ParseQuery('Parent');
        $query->includeKey('children');
        $result = $query->find();
        $this->assertEquals(
            1,
            count($result),
            'Did not return correct number of objects.'
        );
        $children = $result[0]->get('children');
        $length = count($children);
        for ($i = 0; $i < $length; ++$i) {
            $this->assertEquals(
                $i,
                $children[$i]->get('x'),
                'Object should be fetched.'
            );
        }
    }

    public function testIncludeWithNoResults()
    {
        Helper::clearClass('Child');
        Helper::clearClass('Parent');
        $query = new ParseQuery('Parent');
        $query->includeKey('children');
        $result = $query->find();
        $this->assertEquals(
            0,
            count($result),
            'Did not return correct number of objects.'
        );
    }

    public function testIncludeWithNonExistentKey()
    {
        Helper::clearClass('Child');
        Helper::clearClass('Parent');
        $parent = ParseObject::create('Parent');
        $parent->set('foo', 'bar');
        $parent->save();

        $query = new ParseQuery('Parent');
        $query->includeKey('child');
        $results = $query->find();
        $this->assertEquals(
            1,
            count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testIncludeOnTheWrongKeyType()
    {
        Helper::clearClass('Child');
        Helper::clearClass('Parent');
        $parent = ParseObject::create('Parent');
        $parent->set('foo', 'bar');
        $parent->save();

        $query = new ParseQuery('Parent');
        $query->includeKey('foo');
        $results = $query->find();
        $this->assertEquals(
            1,
            count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testIncludeWhenOnlySomeObjectsHaveChildren()
    {
        Helper::clearClass('Child');
        Helper::clearClass('Parent');
        $child = ParseObject::create('Child');
        $child->set('foo', 'bar');
        $child->save();
        $this->saveObjects(
            4,
            function ($i) use ($child) {
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
            4,
            count($results),
            'Did not return correct number of objects.'
        );
        $length = count($results);
        for ($i = 0; $i < $length; ++$i) {
            if ($i & 1) {
                $this->assertEquals(
                    'bar',
                    $results[$i]->get('child')->get('foo'),
                    'Object should be fetched'
                );
            } else {
                $this->assertEquals(
                    null,
                    $results[$i]->get('child'),
                    'Should not have child'
                );
            }
        }
    }

    public function testIncludeMultipleKeys()
    {
        Helper::clearClass('Foo');
        Helper::clearClass('Bar');
        Helper::clearClass('Parent');
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
            'oof',
            $result->get('foofoo')->get('rev'),
            'Object should be fetched'
        );
        $this->assertEquals(
            'rab',
            $result->get('barbar')->get('rev'),
            'Object should be fetched'
        );
    }

    public function testEqualToObject()
    {
        Helper::clearClass('Item');
        Helper::clearClass('Container');
        $items = [];
        $this->saveObjects(
            2,
            function ($i) use (&$items) {
                $items[] = ParseObject::create('Item');
                $items[$i]->set('x', $i);

                return $items[$i];
            }
        );
        $this->saveObjects(
            2,
            function ($i) use ($items) {
                $container = ParseObject::create('Container');
                $container->set('item', $items[$i]);

                return $container;
            }
        );
        $query = new ParseQuery('Container');
        $query->equalTo('item', $items[0]);
        $result = $query->find();
        $this->assertEquals(
            1,
            count($result),
            'Did not return the correct object.'
        );
    }

    public function testEqualToNull()
    {
        $this->saveObjects(
            10,
            function ($i) {
                $obj = ParseObject::create('TestObject');
                $obj->set('num', $i);

                return $obj;
            }
        );
        $this->saveObjects(
            2,
            function ($i) {
                $obj = ParseObject::create('TestObject');
                $obj->set('num', null);

                return $obj;
            }
        );
        $query = new ParseQuery('TestObject');
        $query->equalTo('num', null);
        $results = $query->find();
        $this->assertEquals(
            2,
            count($results),
            'Did not return correct number of objects.'
        );
    }

    public function provideTimeTestObjects()
    {
        Helper::clearClass('TimeObject');
        $items = [];
        $this->saveObjects(
            3,
            function ($i) use (&$items) {
                $timeObject = ParseObject::create('TimeObject');
                $timeObject->set('name', 'item'.$i);
                $timeObject->set('time', new \DateTime());
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
            1,
            count($results),
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
            2,
            count($results),
            'Did not return correct number of objects.'
        );
    }

    public function testRestrictedGetFailsWithoutMasterKey()
    {
        $obj = ParseObject::create('TestObject');
        $restrictedACL = new ParseACL();
        $obj->setACL($restrictedACL);
        $obj->save();
        $query = new ParseQuery('TestObject');
        $this->expectException('Parse\ParseException', 'not found');
        $query->get($obj->getObjectId());
    }

    public function testRestrictedGetWithMasterKey()
    {
        $obj = ParseObject::create('TestObject');
        $restrictedACL = new ParseACL();
        $obj->setACL($restrictedACL);
        $obj->save();

        $query = new ParseQuery('TestObject');
        $objAgain = $query->get($obj->getObjectId(), true);
        $this->assertEquals($obj->getObjectId(), $objAgain->getObjectId());
    }

    public function testRestrictedCount()
    {
        $obj = ParseObject::create('TestObject');
        $restrictedACL = new ParseACL();
        $obj->setACL($restrictedACL);
        $obj->save();

        $query = new ParseQuery('TestObject');
        $count = $query->count();
        $this->assertEquals(0, $count);
        $count = $query->count(true);
        $this->assertEquals(1, $count);
    }

    public function testAscendingByArray()
    {
        $obj = new ParseObject('TestObject');
        $obj->set('name', 'John');
        $obj->set('country', 'US');
        $obj->save();

        $obj = new ParseObject('TestObject');
        $obj->set('name', 'Bob');
        $obj->set('country', 'US');
        $obj->save();

        $obj = new ParseObject('TestObject');
        $obj->set('name', 'Joel');
        $obj->set('country', 'CA');
        $obj->save();

        $query = new ParseQuery('TestObject');
        $query->ascending(['country','name']);
        $results = $query->find();

        $this->assertEquals(3, count($results));

        $this->assertEquals('Joel', $results[0]->name);
        $this->assertEquals('Bob', $results[1]->name);
        $this->assertEquals('John', $results[2]->name);
    }

    public function testOrQueriesVaryingClasses()
    {
        $this->expectException(
            '\Exception',
            'All queries must be for the same class'
        );
        ParseQuery::orQueries([
            new ParseQuery('Class1'),
            new ParseQuery('Class2')
        ]);
    }

    public function testNorQueriesVaryingClasses()
    {
        $this->expectException(
            '\Exception',
            'All queries must be for the same class'
        );
        ParseQuery::norQueries([
            new ParseQuery('Class1'),
            new ParseQuery('Class2')
        ]);
    }

    public function testAndQueriesVaryingClasses()
    {
        $this->expectException(
            '\Exception',
            'All queries must be for the same class'
        );
        ParseQuery::andQueries([
            new ParseQuery('Class1'),
            new ParseQuery('Class2')
        ]);
    }

    public function testQueryFindEncoded()
    {
        $obj = new ParseObject('TestObject');
        $obj->set('name', 'John');
        $obj->set('country', 'US');
        $obj->save();

        $obj = new ParseObject('TestObject');
        $obj->set('name', 'Bob');
        $obj->set('country', 'US');
        $obj->save();

        $obj = new ParseObject('TestObject');
        $obj->set('name', 'Joel');
        $obj->set('country', 'CA');
        $obj->save();

        $query = new ParseQuery('TestObject');
        $query->ascending(['country', 'name']);
        $results = $query->find(false, false);

        $this->assertEquals(3, count($results));

        $this->assertEquals('Joel', $results[0]['name']);
        $this->assertEquals('Bob', $results[1]['name']);
        $this->assertEquals('John', $results[2]['name']);
    }

    public function testQueryNullResponse()
    {
        $obj = new ParseObject('TestObject');
        $obj->set('name', 'John');
        $obj->set('country', 'US');
        $obj->save();

        ParseClient::initialize(
            Helper::$appId,
            Helper::$restKey,
            Helper::$masterKey,
            false,
        );
        ParseClient::setServerURL('http://localhost:1337', 'parse');

        $httpClient = new HttpClientMock();
        $httpClient->setResponse('{}');
        ParseClient::setHttpClient($httpClient);

        $query = new ParseQuery('TestObject');
        $results = $query->find(false);

        $this->assertEquals(0, count($results));

        // Reset HttpClient
        Helper::setUp();
    }

    /**
     * @group query-set-conditions
     */
    public function testSetConditions()
    {
        $query = new ParseQuery('TestObject');
        $query->_setConditions([
            'where' => [
                'key'   => 'value'
            ]
        ]);

        $this->assertEquals([
            'where' => [
                'key'   => 'value'
            ]
        ], $query->_getOptions());
    }

    /**
     * @group query-set-conditions
     */
    public function testGetAndSetConditions()
    {
        $query = new ParseQuery('TestObject');
        $query->equalTo('key', 'value');
        $query->notEqualTo('key2', 'value2');
        $query->includeKey(['include1','include2']);
        $query->excludeKey(['excludeMe','excludeMeToo']);
        $query->readPreference('PRIMARY', 'SECONDARY', 'SECONDARY_PREFERRED');
        $query->contains('container', 'item');
        $query->addDescending('desc');
        $query->addAscending('asc');
        $query->select(['select1','select2']);
        $query->skip(24);

        // sets count = 1
        $query->withCount();
        // reset limit up to 42
        $query->limit(42);

        $conditions = $query->_getOptions();

        $this->assertEquals([
            'where' => [
                'key'   => [
                    '$eq' => 'value',
                ],
                'key2'  => [
                    '$ne'   => 'value2',
                ],
                'container' => [
                    '$regex'    => '\Qitem\E'
                ]
            ],
            'include'   => 'include1,include2',
            'excludeKeys'   => 'excludeMe,excludeMeToo',
            'keys'      => 'select1,select2',
            'limit'     => 42,
            'skip'      => 24,
            'order'     => '-desc,asc',
            'count'     => 1,
            'readPreference'            => 'PRIMARY',
            'includeReadPreference'     => 'SECONDARY',
            'subqueryReadPreference'    => 'SECONDARY_PREFERRED',
        ], $conditions, 'Conditions were different than expected');

        $query2 = new ParseQuery('TestObject');
        $query2->_setConditions($conditions);

        $this->assertEquals(
            $query,
            $query2,
            'Conditions set on query did not give the expected result'
        );
    }

    /**
     * @group query-count-conditions
     */
    public function testCountDoesNotOverrideConditions()
    {
        $obj = new ParseObject('TestObject');
        $obj->set('name', 'John');
        $obj->set('country', 'US');
        $obj->save();

        $obj = new ParseObject('TestObject');
        $obj->set('name', 'Bob');
        $obj->set('country', 'US');
        $obj->save();

        $obj = new ParseObject('TestObject');
        $obj->set('name', 'Mike');
        $obj->set('country', 'CA');
        $obj->save();

        $query = new ParseQuery('TestObject');
        $query->equalTo('country', 'US');
        $query->limit(1);
        $count = $query->count();
        $results = $query->find();

        $this->assertEquals(1, count($results));
        $this->assertEquals(2, $count);

        $this->assertSame([
            'where' => [
                'country' => [
                    '$eq' => 'US'
                ]
            ],
            'limit' => 1,
        ], $query->_getOptions());
    }

    public function testNotArrayConditions()
    {
        $this->expectException(
            '\Parse\ParseException',
            "Conditions must be in an array"
        );

        $query = new ParseQuery('TestObject');
        $query->_setConditions('not-an-array');
    }

    /**
     * @group query-set-conditions
     */
    public function testUnknownCondition()
    {
        $this->expectException(
            '\Parse\ParseException',
            'Unknown condition to set'
        );

        $query = new ParseQuery('TestObject');
        $query->_setConditions([
            'unrecognized'  => 1
        ]);
    }

    /**
     * @group query-equalTo-conditions
     */
    public function testEqualToWithSameKeyDoesNotOverrideOtherConditions()
    {
        $baz = new ParseObject('TestObject');
        $baz->setArray('fooStack', [
            [
                'status' => 'baz'
            ],
            [
                'status' => 'bar'
            ]
        ]);
        $baz->save();

        $bar = new ParseObject('TestObject');
        $bar->setArray('fooStack', [
            [
                'status' => 'bar'
            ]
        ]);
        $bar->save();

        $qux = new ParseObject('TestObject');
        $qux->setArray('fooStack', [
            [
                'status' => 'bar',
            ],
            [
                'status' => 'qux'
            ]
        ]);
        $qux->save();

        $query = new ParseQuery('TestObject');
        $query->notEqualTo('fooStack.status', 'baz');
        $query->equalTo('fooStack.status', 'bar');

        $this->assertEquals(2, $query->count(true));

        $this->assertSame([
            'where' => [
                'fooStack.status'   => [
                    '$ne'   => 'baz',
                    '$eq' => 'bar',
                ]
            ],
        ], $query->_getOptions());
    }
}

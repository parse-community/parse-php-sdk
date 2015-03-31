<?php

use Parse\ParseException;
use Parse\ParseObject;
use Parse\ParseQuery;

require_once 'ParseTestHelper.php';

class IncrementTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        ParseTestHelper::setUp();
    }

    public function tearDown()
    {
        ParseTestHelper::clearClass("TestObject");
        ParseTestHelper::tearDown();
    }

    public function testIncrementOnFreshObject()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('yo', 1);
        $obj->increment('yo');
        $obj->save();
        $query = new ParseQuery('TestObject');
        $query->equalTo('objectId', $obj->getObjectId());
        $result = $query->first();
        $this->assertEquals($result->get('yo'), 2, 'Increment did not work');
    }

    public function testIncrement()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('yo', 1);
        $obj->save();
        $obj->increment('yo', 1);
        $obj->save();
        $query = new ParseQuery('TestObject');
        $query->equalTo('objectId', $obj->getObjectId());
        $result = $query->first();
        $this->assertEquals($result->get('yo'), 2, 'Increment did not work');
    }

    public function testIncrementByValue()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('yo', 1);
        $obj->save();
        $obj->increment('yo', 5);
        $obj->save();
        $query = new ParseQuery('TestObject');
        $query->equalTo('objectId', $obj->getObjectId());
        $result = $query->first();
        $this->assertEquals($result->get('yo'), 6, 'Increment did not work');
    }

    public function testIncrementNegative()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('yo', 1);
        $obj->save();
        $obj->increment('yo', -1);
        $obj->save();
        $query = new ParseQuery('TestObject');
        $query->equalTo('objectId', $obj->getObjectId());
        $result = $query->first();
        $this->assertEquals($result->get('yo'), 0, 'Increment did not work');
    }

    public function testIncrementFloat()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('yo', 1);
        $obj->save();
        $obj->increment('yo', 1.5);
        $obj->save();
        $query = new ParseQuery('TestObject');
        $query->equalTo('objectId', $obj->getObjectId());
        $result = $query->first();
        $this->assertEquals($result->get('yo'), 2.5, 'Increment did not work');
    }

    public function testIncrementAtomic()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('yo', 1);
        $obj->save();
        $query = new ParseQuery('TestObject');
        $query->equalTo('objectId', $obj->getObjectId());
        $objAgainOne = $query->first();
        $queryAgain = new ParseQuery('TestObject');
        $queryAgain->equalTo('objectId', $objAgainOne->getObjectId());
        $objAgainTwo = $queryAgain->first();
        $objAgainOne->increment('yo');
        $objAgainTwo->increment('yo');
        $objAgainOne->save();
        $objAgainOne->increment('yo');
        $objAgainOne->save();
        $objAgainTwo->save();
        $queryAgainTwo = new ParseQuery('TestObject');
        $queryAgainTwo->equalTo('objectId', $objAgainTwo->getObjectId());
        $objAgainThree = $query->first();
        $this->assertEquals($objAgainThree->get('yo'), 4);
    }

    public function testIncrementGetsValueBack()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('yo', 1);
        $obj->save();
        $query = new ParseQuery('TestObject');
        $query->equalTo('objectId', $obj->getObjectId());
        $objAgainOne = $query->first();
        $obj->increment('yo');
        $obj->save();
        $objAgainOne->increment('yo');
        $objAgainOne->save();
        $this->assertEquals($objAgainOne->get('yo'), 3);
    }

    public function testIncrementWithOtherUpdates()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('yo', 1);
        $obj->set('foo', 'bar');
        $obj->save();
        $query = new ParseQuery('TestObject');
        $query->equalTo('objectId', $obj->getObjectId());
        $objAgainOne = $query->first();
        $objAgainOne->increment('yo');
        $objAgainOne->set('foo', 'parse');
        $objAgainOne->save();
        $queryAgain = new ParseQuery('TestObject');
        $queryAgain->equalTo('objectId', $objAgainOne->getObjectId());
        $objAgainTwo = $queryAgain->first();
        $this->assertEquals($objAgainOne->get('foo'), 'parse');
        $this->assertEquals($objAgainOne->get('yo'), 2);
    }

    public function testIncrementNonNumber()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->save();
        $this->setExpectedException(
            'Parse\ParseException', 'Cannot increment a non-number type'
        );
        $obj->increment('foo');
        $obj->save();
    }

    public function testIncrementOnDeletedField()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('yo', 1);
        $obj->save();
        $obj->delete('yo');
        $obj->increment('yo');
        $query = new ParseQuery('TestObject');
        $query->equalTo('objectId', $obj->getObjectId());
        $result = $query->first();
        $this->assertEquals(
            $result->get('yo'), 1, 'Error in increment on deleted field'
        );
    }

    public function testIncrementEmptyFieldOnFreshObject()
    {
        $obj = ParseObject::create('TestObject');
        $obj->increment('yo');
        $obj->save();
        $query = new ParseQuery('TestObject');
        $query->equalTo('objectId', $obj->getObjectId());
        $result = $query->first();
        $this->assertEquals(
            $result->get('yo'), 1,
            'Error in increment on empty field of fresh object'
        );
    }

    public function testIncrementEmptyField()
    {
        $obj = ParseObject::create('TestObject');
        $obj->save();
        $query = new ParseQuery('TestObject');
        $query->equalTo('objectId', $obj->getObjectId());
        $objAgain = $query->first();
        $obj->increment('yo');
        $objAgain->increment('yo');
        $obj->save();
        $objAgain->save();
        $queryAgain = new ParseQuery('TestObject');
        $queryAgain->equalTo('objectId', $objAgain->getObjectId());
        $objectAgainTwo = $queryAgain->first();
        $this->assertEquals(
            $objectAgainTwo->get('yo'), 2,
            'Error in increment on empty field'
        );
    }

    public function testIncrementEmptyFieldAndTypeConflict()
    {
        $obj = ParseObject::create('TestObject');
        $obj->save();
        $query = new ParseQuery('TestObject');
        $query->equalTo('objectId', $obj->getObjectId());
        $objAgain = $query->first();
        $obj->set('randomkey', 'bar');
        $obj->save();
        $objAgain->increment('randomkey');
        $this->setExpectedException(
            'Parse\ParseException',
            "invalid type for key"
        );
        $objAgain->save();
    }

    public function testIncrementEmptyFieldSolidifiesType()
    {
        $obj = ParseObject::create('TestObject');
        $obj->save();
        $query = new ParseQuery('TestObject');
        $query->equalTo('objectId', $obj->getObjectId());
        $objAgain = $query->first();
        $objAgain->set('randomkeyagain', 'bar');
        $obj->increment('randomkeyagain');
        $obj->save();
        $this->setExpectedException(
            'Parse\ParseException',
            'invalid type for key randomkeyagain, '.
            'expected number, but got string'
        );
        $objAgain->save();
    }
}

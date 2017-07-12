<?php

namespace Parse\Test;

use Parse\HttpClients\ParseCurlHttpClient;
use Parse\HttpClients\ParseStreamHttpClient;
use Parse\Internal\SetOperation;
use Parse\ParseACL;
use Parse\ParseClient;
use Parse\ParseInstallation;
use Parse\ParseObject;
use Parse\ParsePushStatus;
use Parse\ParseQuery;
use Parse\ParseRole;
use Parse\ParseSession;
use Parse\ParseUser;

class ParseObjectTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Helper::setUp();
    }

    public function setUp()
    {
        Helper::setHttpClient();
    }

    public function tearDown()
    {
        Helper::tearDown();
    }

    public function testCreate()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('test', 'test');
        $obj->save();
    }

    public function testUpdate()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->save();
        $obj->set('foo', 'changed');
        $obj->save();
        $this->assertEquals(
            $obj->foo,
            'changed',
            'Update should have succeeded'
        );
    }

    public function testSaveCycle()
    {
        $a = ParseObject::create('TestObject');
        $b = ParseObject::create('TestObject');
        $a->set('b', $b);
        $a->save();
        $this->assertFalse($a->isDirty());
        $this->assertNotNull($a->getObjectId());
        $this->assertNotNull($b->getObjectId());
        $b->set('a', $a);
        $b->save();
        $this->assertEquals($b, $a->get('b'));
        $this->assertEquals($a, $b->get('a'));
    }

    public function testReturnedObjectIsAParseObject()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->save();

        $query = new ParseQuery('TestObject');
        $returnedObject = $query->get($obj->getObjectId());
        $this->assertTrue(
            $returnedObject instanceof ParseObject,
            'Returned object was not a ParseObject'
        );
        $this->assertEquals(
            'bar',
            $returnedObject->foo,
            'Value of foo was not saved.'
        );
    }

    public function testFetch()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('test', 'test');
        $obj->save();
        $t2 = ParseObject::create('TestObject', $obj->getObjectId());
        $t2->fetch();
        $this->assertEquals('test', $t2->get('test'), 'Fetch failed.');
    }

    public function testDeleteStream()
    {
        ParseClient::setHttpClient(new ParseStreamHttpClient());

        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->save();
        $obj->destroy();
        $query = new ParseQuery('TestObject');
        $this->setExpectedException('Parse\ParseException', 'Object not found');
        $out = $query->get($obj->getObjectId());
    }

    public function testDeleteCurl()
    {
        if (function_exists('curl_init')) {
            ParseClient::setHttpClient(new ParseCurlHttpClient());

            $obj = ParseObject::create('TestObject');
            $obj->set('foo', 'bar');
            $obj->save();
            $obj->destroy();
            $query = new ParseQuery('TestObject');
            $this->setExpectedException('Parse\ParseException', 'Object not found');
            $out = $query->get($obj->getObjectId());
        }
    }

    public function testFind()
    {
        Helper::clearClass('TestObject');
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->save();
        $query = new ParseQuery('TestObject');
        $query->equalTo('foo', 'bar');
        $response = $query->count();
        $this->assertTrue($response == 1);
    }

    public function testRelationalFields()
    {
        Helper::clearClass('Item');
        Helper::clearClass('Container');
        $item = ParseObject::create('Item');
        $item->set('property', 'x');
        $item->save();

        $container = ParseObject::create('Container');
        $container->set('item', $item);
        $container->save();

        $query = new ParseQuery('Container');
        $query->includeKey('item');
        $containerAgain = $query->get($container->getObjectId());
        $itemAgain = $containerAgain->get('item');
        $this->assertEquals('x', $itemAgain->get('property'));

        $query->equalTo('item', $item);
        $results = $query->find();
        $this->assertEquals(1, count($results));
    }

    public function testRelationDeletion()
    {
        Helper::clearClass('SimpleObject');
        Helper::clearClass('Child');
        $simple = ParseObject::create('SimpleObject');
        $child = ParseObject::create('Child');
        $simple->set('child', $child);
        $simple->save();
        $this->assertNotNull($simple->get('child'));
        $simple->delete('child');
        $this->assertNull($simple->get('child'));
        $this->assertTrue($simple->isDirty());
        $this->assertTrue($simple->isKeyDirty('child'));
        $simple->save();
        $this->assertNull($simple->get('child'));
        $this->assertFalse($simple->isDirty());
        $this->assertFalse($simple->isKeyDirty('child'));

        $query = new ParseQuery('SimpleObject');
        $simpleAgain = $query->get($simple->getObjectId());
        $this->assertNull($simpleAgain->get('child'));
    }

    public function testSaveAddsNoDataKeys()
    {
        $obj = ParseObject::create('TestObject');
        $obj->save();
        $json = $obj->_encode();
        $data = get_object_vars(json_decode($json));
        unset($data['objectId']);
        unset($data['createdAt']);
        unset($data['updatedAt']);
        $this->assertEquals(0, count($data));
    }

    public function testRecursiveSave()
    {
        Helper::clearClass('Container');
        Helper::clearClass('Item');
        $a = ParseObject::create('Container');
        $b = ParseObject::create('Item');
        $b->set('foo', 'bar');
        $a->set('item', $b);
        $a->save();
        $query = new ParseQuery('Container');
        $result = $query->find();
        $this->assertEquals(1, count($result));
        $containerAgain = $result[0];
        $itemAgain = $containerAgain->get('item');
        $itemAgain->fetch();
        $this->assertEquals('bar', $itemAgain->get('foo'));
    }

    public function testFetchRemovesOldFields()
    {
        $obj = ParseObject::create('SimpleObject');
        $obj->set('foo', 'bar');
        $obj->set('test', 'foo');
        $obj->save();

        $query = new ParseQuery('SimpleObject');
        $object1 = $query->get($obj->getObjectId());
        $object2 = $query->get($obj->getObjectId());
        $this->assertEquals('foo', $object1->get('test'));
        $this->assertEquals('foo', $object2->get('test'));
        $object2->delete('test');
        $this->assertEquals('foo', $object1->get('test'));
        $object2->save();
        $object1->fetch();
        $this->assertEquals(null, $object1->get('test'));
        $this->assertEquals(null, $object2->get('test'));
        $this->assertEquals('bar', $object1->get('foo'));
        $this->assertEquals('bar', $object2->get('foo'));
    }

    public function testCreatedAtAndUpdatedAtExposed()
    {
        $obj = ParseObject::create('TestObject');
        $obj->save();
        $this->assertNotNull($obj->getObjectId());
        $this->assertNotNull($obj->getCreatedAt());
        $this->assertNotNull($obj->getUpdatedAt());
    }

    public function testCreatedAtDoesNotChange()
    {
        $obj = ParseObject::create('TestObject');
        $obj->save();
        $this->assertNotNull($obj->getObjectId());
        $objAgain = ParseObject::create('TestObject', $obj->getObjectId());
        $objAgain->fetch();
        $this->assertEquals(
            $obj->getCreatedAt(),
            $objAgain->getCreatedAt()
        );
    }

    public function testUpdatedAtGetsUpdated()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->save();
        $this->assertNotNull($obj->getUpdatedAt());
        $firstUpdate = $obj->getUpdatedAt();
        // Parse is so fast, this test was flaky as the \DateTimes were equal.
        sleep(1);
        $obj->set('foo', 'baz');
        $obj->save();
        $this->assertNotEquals($obj->getUpdatedAt(), $firstUpdate);
    }

    public function testCreatedAtIsReasonable()
    {
        $startTime = new \DateTime();
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->save();
        $endTime = new \DateTime();
        $startDiff = abs(
            $startTime->getTimestamp() - $obj->getCreatedAt()->getTimestamp()
        );
        $endDiff = abs(
            $endTime->getTimestamp() - $obj->getCreatedAt()->getTimestamp()
        );
        $this->assertLessThan(5000, $startDiff);
        $this->assertLessThan(5000, $endDiff);
    }

    public function testCanSetNull()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', null);
        $obj->save();
        $this->assertEquals(null, $obj->get('foo'));
    }

    public function testCanSetBoolean()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('yes', true);
        $obj->set('no', false);
        $obj->save();
        $this->assertTrue($obj->get('yes'));
        $this->assertFalse($obj->get('no'));
    }

    public function testInvalidClassName()
    {
        $obj = ParseObject::create('Foo^bar');
        $this->setExpectedException('Parse\ParseException', 'schema class name does not revalidate');
        $obj->save();
    }

    public function testInvalidKeyName()
    {
        $obj = ParseObject::create('TestItem');
        $obj->set('foo^bar', 'baz');
        $this->setExpectedException(
            'Parse\ParseException',
            'Invalid field name: foo^bar.'
        );
        $obj->save();
    }

    public function testSimpleFieldDeletion()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->save();
        $obj->delete('foo');
        $this->assertFalse($obj->has('foo'), 'foo should have been unset.');
        $this->assertTrue($obj->isKeyDirty('foo'), 'foo should be dirty.');
        $this->assertTrue($obj->isDirty(), 'the whole object should be dirty.');
        $obj->save();
        $this->assertFalse($obj->has('foo'), 'foo should have been unset.');
        $this->assertFalse($obj->isKeyDirty('foo'), 'object was just saved.');
        $this->assertFalse($obj->isDirty(), 'object was just saved.');

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('foo'), 'foo was not removed.');
    }

    public function testFieldDeletionBeforeFirstSave()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->delete('foo');
        $this->assertFalse($obj->has('foo'), 'foo should have been unset.');
        $this->assertTrue($obj->isKeyDirty('foo'), 'foo should be dirty.');
        $this->assertTrue($obj->isDirty(), 'the whole object should be dirty.');
        $obj->save();
        $this->assertFalse($obj->has('foo'), 'foo should have been unset.');
        $this->assertFalse($obj->isKeyDirty('foo'), 'object was just saved.');
        $this->assertFalse($obj->isDirty(), 'object was just saved.');
    }

    public function testDeletedKeysGetCleared()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->delete('foo');
        $obj->save();
        $obj->set('foo', 'baz');
        $obj->save();

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertEquals('baz', $result->get('foo'));
    }

    public function testSettingAfterDeleting()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->save();
        $obj->delete('foo');
        $obj->set('foo', 'baz');
        $obj->save();

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertEquals('baz', $result->get('foo'));
    }

    public function testDirtyKeys()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('cat', 'good');
        $obj->set('dog', 'bad');
        $obj->save();
        $this->assertFalse($obj->isDirty());
        $this->assertFalse($obj->isKeyDirty('cat'));
        $this->assertFalse($obj->isKeyDirty('dog'));
        $obj->set('dog', 'okay');
        $this->assertTrue($obj->isKeyDirty('dog'));
        $this->assertTrue($obj->isDirty());
    }

    public function testOldAttributeUnsetThenUnset()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('x', 3);
        $obj->save();
        $obj->delete('x');
        $obj->delete('x');
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function testNewAttributeUnsetThenUnset()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('x', 5);
        $obj->delete('x');
        $obj->delete('x');
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function testUnknownAttributeUnsetThenUnset()
    {
        $obj = ParseObject::create('TestObject');
        $obj->delete('x');
        $obj->delete('x');
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function oldAttributeUnsetThenClear()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('x', 3);
        $obj->save();
        $obj->delete('x');
        $obj->clear();
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function testNewAttributeUnsetThenClear()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('x', 5);
        $obj->delete('x');
        $obj->clear();
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function testUnknownAttributeUnsetThenClear()
    {
        $obj = ParseObject::create('TestObject');
        $obj->delete('x');
        $obj->clear();
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function oldAttributeClearThenUnset()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('x', 3);
        $obj->save();
        $obj->clear();
        $obj->delete('x');
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function testNewAttributeClearThenUnset()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('x', 5);
        $obj->clear();
        $obj->delete('x');
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function testUnknownAttributeClearThenUnset()
    {
        $obj = ParseObject::create('TestObject');
        $obj->clear();
        $obj->delete('x');
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function oldAttributeClearThenClear()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('x', 3);
        $obj->save();
        $obj->clear();
        $obj->clear();
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function testNewAttributeClearThenClear()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('x', 5);
        $obj->clear();
        $obj->clear();
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function testUnknownAttributeClearThenClear()
    {
        $obj = ParseObject::create('TestObject');
        $obj->clear();
        $obj->clear();
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function testSavingChildrenInArray()
    {
        Helper::clearClass('Parent');
        Helper::clearClass('Child');
        $parent = ParseObject::create('Parent');
        $child1 = ParseObject::create('Child');
        $child2 = ParseObject::create('Child');
        $child1->set('name', 'tyrian');
        $child2->set('name', 'cersei');
        $parent->setArray('children', [$child1, $child2]);
        $parent->save();

        $query = new ParseQuery('Child');
        $query->ascending('name');
        $results = $query->find();
        $this->assertEquals(2, count($results));
        $this->assertEquals('cersei', $results[0]->get('name'));
        $this->assertEquals('tyrian', $results[1]->get('name'));
    }

    public function testManySaveAfterAFailure()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('number', 1);
        $obj->save();
        $obj2 = ParseObject::create('TestObject');
        $obj2->set('number', 'two');
        $exceptions = 0;
        try {
            $obj2->save();
        } catch (\Parse\ParseException $pe) {
            $exceptions++;
        }
        $obj2->set('foo', 'bar');
        try {
            $obj2->save();
        } catch (\Parse\ParseException $pe) {
            $exceptions++;
        }
        $obj2->set('foo', 'baz');
        try {
            $obj2->save();
        } catch (\Parse\ParseException $pe) {
            $exceptions++;
        }
        $obj2->set('number', 3);
        $obj2->save();
        if ($exceptions != 3) {
            $this->fail('Did not cause expected # of exceptions.');
        }
    }

    public function testNewKeyIsDirtyAfterSave()
    {
        $obj = ParseObject::create('TestObject');
        $obj->save();
        $obj->set('content', 'x');
        $obj->fetch();
        $this->assertTrue($obj->isKeyDirty('content'));
    }

    public function testAddWithAnObject()
    {
        $parent = ParseObject::create('Person');
        $child = ParseObject::create('Person');
        $child->save();
        $parent->add('children', [$child]);
        $parent->save();

        $query = new ParseQuery('Person');
        $parentAgain = $query->get($parent->getObjectId());
        $children = $parentAgain->get('children');
        $this->assertEquals(
            $child->getObjectId(),
            $children[0]->getObjectId()
        );
    }

    public function testSetArray()
    {
        $arr = [0 => 'foo', 2 => 'bar'];
        $obj = ParseObject::create('TestObject');
        $obj->setArray('arr', $arr);
        $obj->save();

        $this->assertEquals($obj->get('arr'), array_values($arr));
    }

    public function testAddUnique()
    {
        $obj = ParseObject::create('TestObject');
        $obj->setArray('arr', [1, 2, 3]);
        $obj->addUnique('arr', [1]);
        $this->assertEquals(3, count($obj->get('arr')));
        $obj->addUnique('arr', [4]);
        $this->assertEquals(4, count($obj->get('arr')));

        $obj->save();
        $obj2 = ParseObject::create('TestObject');
        $obj3 = ParseObject::create('TestObject');
        $obj2->save();
        $obj3->save();

        $obj4 = ParseObject::create('TestObject');
        $obj4->setArray('parseObjects', [$obj, $obj2]);
        $obj4->save();
        $obj4->addUnique('parseObjects', [$obj3]);
        $this->assertEquals(3, count($obj4->get('parseObjects')));
        $obj4->addUnique('parseObjects', [$obj2]);
        $this->assertEquals(3, count($obj4->get('parseObjects')));
    }

    public function testToJSONSavedObject()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->save();
        $json = $obj->_encode();
        $decoded = json_decode($json);
        $this->assertTrue(isset($decoded->objectId));
        $this->assertTrue(isset($decoded->createdAt));
        $this->assertTrue(isset($decoded->updatedAt));
        $this->assertTrue(isset($decoded->foo));
    }

    public function testToJSONUnsavedObject()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $json = $obj->_encode();
        $decoded = json_decode($json);
        $this->assertFalse(isset($decoded->objectId));
        $this->assertFalse(isset($decoded->createdAt));
        $this->assertFalse(isset($decoded->updatedAt));
        $this->assertTrue(isset($decoded->foo));
    }

    public function testAssocToJSONSavedObject()
    {
        $obj = ParseObject::create('TestObject');
        $assoc = ["foo" => "bar", "baz" => "yay"];
        $obj->setAssociativeArray('obj', $assoc);
        $obj->save();
        $json = $obj->_encode();
        $decoded = json_decode($json, true);
        $this->assertEquals($decoded['obj'], $assoc);
        $this->assertEquals($obj->get('obj'), $assoc);
    }

    public function testAssocToJSONUnsavedObject()
    {
        $obj = ParseObject::create('TestObject');
        $assoc = ["foo" => "bar", "baz" => "yay"];
        $obj->setAssociativeArray('obj', $assoc);
        $json = $obj->_encode();
        $decoded = json_decode($json, true);
        $this->assertEquals($decoded['obj'], $assoc);
        $this->assertEquals($obj->get('obj'), $assoc);
    }

    public function testRemoveOperation()
    {
        $obj = ParseObject::create('TestObject');
        $obj->setArray('arr', [1, 2, 3]);
        $obj->save();
        $this->assertEquals(3, count($obj->get('arr')));
        $obj->remove('arr', 1);
        $this->assertEquals(2, count($obj->get('arr')));
        $obj->remove('arr', 1);
        $obj->save();
        $query = new ParseQuery('TestObject');
        $objAgain = $query->get($obj->getObjectId());
        $this->assertEquals(2, count($objAgain->get('arr')));
        $objAgain->remove('arr', 2);
        $this->assertEquals(1, count($objAgain->get('arr')));
    }

    public function testRemoveOperationWithParseObjects()
    {
        $o1 = ParseObject::create('TestObject');
        $o2 = ParseObject::create('TestObject');
        $o3 = ParseObject::create('TestObject');
        ParseObject::saveAll([$o1, $o2, $o3]);
        $obj = ParseObject::create('TestObject');
        $obj->setArray('objs', [$o1, $o2, $o3]);
        $obj->save();
        $this->assertEquals(3, count($obj->get('objs')));
        $obj->remove('objs', $o3);
        $this->assertEquals(2, count($obj->get('objs')));
        $obj->remove('objs', $o3);
        $obj->save();
        $query = new ParseQuery('TestObject');
        $objAgain = $query->get($obj->getObjectId());
        $this->assertEquals(2, count($objAgain->get('objs')));
        $objAgain->remove('objs', $o2);
        $this->assertEquals(1, count($objAgain->get('objs')));
    }

    public function testDestroyAll()
    {
        Helper::clearClass('TestObject');

        // log in
        $user = new ParseUser();
        $user->setUsername('username123');
        $user->setPassword('password123');
        $user->signUp();

        $o1 = ParseObject::create('TestObject');
        $o2 = ParseObject::create('TestObject');
        $o3 = ParseObject::create('TestObject');
        ParseObject::saveAll([$o1, $o2, $o3]);
        ParseObject::destroyAll([$o1, $o2, $o3]);
        $query = new ParseQuery('TestObject');
        $results = $query->find();
        $this->assertEquals(0, count($results));

        ParseUser::logOut();
        $user->destroy(true);
    }

    public function testEmptyArray()
    {
        $obj = ParseObject::create('TestObject');
        $obj->setArray('baz', []);
        $obj->save();
        $query = new ParseQuery('TestObject');
        $returnedObject = $query->get($obj->getObjectId());
        $this->assertTrue(
            is_array($returnedObject->get('baz')),
            'Value was not stored as an array.'
        );
        $this->assertEquals(0, count($returnedObject->get('baz')));
    }

    public function testArraySetAndAdd()
    {
        $obj = ParseObject::create('TestObject');
        $obj->setArray('arrayfield', ['a', 'b']);
        $obj->save();
        $obj->add('arrayfield', ['c', 'd', 'e']);
        $obj->save();
    }

    public function testObjectIsDirty()
    {
        $obj = ParseObject::create('Gogo');
        $key1 = 'awesome';
        $key2 = 'great';
        $key3 = 'arrayKey';
        $value1 = 'very true';
        $value2 = true;

        $obj->set($key1, $value1);
        $this->assertTrue($obj->isKeyDirty($key1));
        $this->assertFalse($obj->isKeyDirty($key2));
        $this->assertTrue($obj->isDirty());

        $obj->save();
        $this->assertFalse($obj->isKeyDirty($key1));
        $this->assertFalse($obj->isKeyDirty($key2));
        $this->assertFalse($obj->isDirty());

        $obj->set($key2, $value2);
        $this->assertTrue($obj->isKeyDirty($key2));
        $this->assertFalse($obj->isKeyDirty($key1));
        $this->assertTrue($obj->isDirty());

        $query = new ParseQuery('Gogo');
        $queriedObj = $query->get($obj->getObjectId());
        $this->assertEquals($value1, $queriedObj->get($key1));
        $this->assertFalse($queriedObj->get($key2) === $value2);

        // check dirtiness of queried item
        $this->assertFalse($queriedObj->isKeyDirty($key1));
        $this->assertFalse($queriedObj->isKeyDirty($key2));
        $this->assertFalse($queriedObj->isDirty());

        $obj->save();
        $queriedObj = $query->get($obj->getObjectId());
        $this->assertEquals($value1, $queriedObj->get($key1));
        $this->assertEquals($value2, $queriedObj->get($key2));
        $this->assertFalse($queriedObj->isKeyDirty($key1));
        $this->assertFalse($queriedObj->isKeyDirty($key2));
        $this->assertFalse($queriedObj->isDirty());

        // check array
        $obj->add($key3, [$value1, $value2, $value1]);
        $this->assertTrue($obj->isDirty());

        $obj->save();
        $this->assertFalse($obj->isDirty());
    }

    public function testObjectIsDirtyWithChildren()
    {
        $obj = ParseObject::create('Sito');
        $key = 'testKey';
        $childKey = 'testChildKey';
        $childSimultaneousKey = 'testChildKeySimultaneous';
        $value = 'someRandomValue';
        $child = ParseObject::create('Sito');
        $childSimultaneous = ParseObject::create('Sito');
        $childArray1 = ParseObject::create('Sito');
        $childArray2 = ParseObject::create('Sito');

        $child->set('randomKey', 'randomValue');
        $this->assertTrue($child->isDirty());

        $obj->set($key, $value);
        $this->assertTrue($obj->isDirty());

        $obj->save();
        $this->assertFalse($obj->isDirty());

        $obj->set($childKey, $child);
        $this->assertTrue($obj->isKeyDirty($childKey));
        $this->assertTrue($obj->isDirty());

        // check when child is saved, parent should still be dirty
        $child->save();
        $this->assertFalse($child->isDirty());
        $this->assertTrue($obj->isDirty());

        $obj->save();
        $this->assertFalse($child->isDirty());
        $this->assertFalse($obj->isDirty());

        $childSimultaneous->set('randomKey', 'randomValue');
        $obj->set($childSimultaneousKey, $childSimultaneous);
        $this->assertTrue($obj->isDirty());

        // check case with array
        $childArray1->set('random', 'random2');
        $obj->add('arrayKey', [$childArray1, $childArray2]);
        $this->assertTrue($obj->isDirty());
        $childArray1->save();
        $childArray2->save();
        $this->assertFalse($childArray1->getObjectId() === null);
        $this->assertFalse($childArray2->getObjectId() === null);
        $this->assertFalse($obj->getObjectId() === null);
        $this->assertTrue($obj->isDirty());
        $obj->save();
        $this->assertFalse($obj->isDirty());

        // check simultaneous save
        $obj->save();
        $this->assertFalse($obj->isDirty());
        $this->assertFalse($childSimultaneous->isDirty());
    }

    public function testSaveAllStream()
    {
        ParseClient::setHttpClient(new ParseStreamHttpClient());

        Helper::clearClass('TestObject');
        $objs = [];
        for ($i = 1; $i <= 90; $i++) {
            $obj = ParseObject::create('TestObject');
            $obj->set('test', 'test');
            $objs[] = $obj;
        }
        ParseObject::saveAll($objs);
        $query = new ParseQuery('TestObject');
        $result = $query->find();
        $this->assertEquals(90, count($result));
    }

    public function testSaveAllCurl()
    {
        if (function_exists('curl_init')) {
            ParseClient::setHttpClient(new ParseCurlHttpClient());

            Helper::clearClass('TestObject');
            $objs = [];
            for ($i = 1; $i <= 90; $i++) {
                $obj = ParseObject::create('TestObject');
                $obj->set('test', 'test');
                $objs[] = $obj;
            }
            ParseObject::saveAll($objs);
            $query = new ParseQuery('TestObject');
            $result = $query->find();
            $this->assertEquals(90, count($result));
        }
    }

    /**
     * @group test-empty-objects-arrays
     */
    public function testEmptyObjectsAndArrays()
    {
        $obj = ParseObject::create('TestObject');
        $obj->setArray('arr', []);
        $obj->setAssociativeArray('obj', []);
        $saveOpArray = new SetOperation([]);
        $saveOpAssoc = new SetOperation([], true);
        $this->assertTrue(
            is_array($saveOpArray->_encode()),
            'Value should be array.'
        );
        $this->assertTrue(
            is_object($saveOpAssoc->_encode()),
            'Value should be object.'
        );
        $obj->save();
        $obj->setAssociativeArray(
            'obj',
            [
                'foo' => 'bar',
                'baz' => 'yay',
            ]
        );
        $obj->save();
        $query = new ParseQuery('TestObject');
        $objAgain = $query->get($obj->getObjectId());
        $this->assertTrue(is_array($objAgain->get('arr')));
        $this->assertTrue(is_array($objAgain->get('obj')));
        $this->assertEquals('bar', $objAgain->get('obj')['foo']);
        $this->assertEquals('yay', $objAgain->get('obj')['baz']);
    }

    public function testDatetimeHandling()
    {
        $date = new \DateTime('2014-04-30T12:34:56.789Z');
        $obj = ParseObject::create('TestObject');
        $obj->set('f8', $date);
        $obj->save();
        $query = new ParseQuery('TestObject');
        $objAgain = $query->get($obj->getObjectId());
        $dateAgain = $objAgain->get('f8');
        $this->assertTrue($date->getTimestamp() == $dateAgain->getTimestamp());
    }

    public function testBatchSaveExceptions()
    {
        $obj1 = ParseObject::create('TestObject');
        $obj2 = ParseObject::create('TestObject');
        $obj1->set('fos^^co', 'hi');
        $obj2->set('fo^^mo', 'hi');
        try {
            ParseObject::saveAll([$obj1, $obj2]);
            $this->fail('Save should have failed.');
        } catch (\Parse\ParseAggregateException $ex) {
            $errors = $ex->getErrors();
            $this->assertEquals('Invalid field name: fos^^co.', $errors[0]['error']);
            $this->assertEquals('Invalid field name: fo^^mo.', $errors[1]['error']);
        }
    }

    public function testFetchAll()
    {
        $obj1 = ParseObject::create('TestObject');
        $obj2 = ParseObject::create('TestObject');
        $obj3 = ParseObject::create('TestObject');
        $obj1->set('foo', 'bar');
        $obj2->set('foo', 'bar');
        $obj3->set('foo', 'bar');
        ParseObject::saveAll([$obj1, $obj2, $obj3]);
        $newObj1 = ParseObject::create('TestObject', $obj1->getObjectId());
        $newObj2 = ParseObject::create('TestObject', $obj2->getObjectId());
        $newObj3 = ParseObject::create('TestObject', $obj3->getObjectId());
        $results = ParseObject::fetchAll([$newObj1, $newObj2, $newObj3]);
        $this->assertEquals(3, count($results));
        $this->assertEquals('bar', $results[0]->get('foo'));
        $this->assertEquals('bar', $results[1]->get('foo'));
        $this->assertEquals('bar', $results[2]->get('foo'));
    }

    public function testNoRegisteredSubclasses()
    {
        $this->setExpectedException(
            '\Exception',
            'You must initialize the ParseClient using ParseClient::initialize '.
            'and your Parse API keys before you can begin working with Objects.'
        );
        ParseUser::_unregisterSubclass();
        ParseRole::_unregisterSubclass();
        ParseInstallation::_unregisterSubclass();
        ParseSession::_unregisterSubclass();
        ParsePushStatus::_unregisterSubclass();

        new ParseObject('TestClass');
    }

    public function testMissingClassName()
    {
        Helper::setUp();

        $this->setExpectedException(
            '\Exception',
            'You must specify a Parse class name or register the appropriate '.
            'subclass when creating a new Object.    Use ParseObject::create to '.
            'create a subclass object.'
        );

        new ParseObjectMock();
    }

    public function testSettingProperties()
    {
        $obj = new ParseObject('TestClass');
        $obj->key = "value";

        $this->assertEquals('value', $obj->get('key'));
    }

    public function testSettingProtectedProperty()
    {
        $this->setExpectedException(
            '\Exception',
            'Protected field could not be set.'
        );
        $obj = new ParseObject('TestClass');
        $obj->updatedAt = "value";
    }

    public function testGettingProperties()
    {
        $obj = new ParseObject('TestClass');
        $obj->key = "value";
        $this->assertEquals('value', $obj->key);
    }

    public function testNullValues()
    {
        $obj = new ParseObject('TestClass');
        $obj->key1 = 'notnull';
        $obj->key2 = null;

        // verify key2 is present
        $this->assertNull($obj->get('key2'));

        $obj->save();
        $obj->fetch();

        // verify we still have key2 present
        $this->assertNull($obj->get('key2'));

        $obj->destroy();
    }

    public function testIsset()
    {
        $obj = new ParseObject('TestClass');
        $obj->set('key', 'value');
        $this->assertTrue(isset($obj->key), 'Failed on "value"');

        $obj->set('key', 9);
        $this->assertTrue(isset($obj->key), 'Failed on 9');

        $obj->set('key', 0);
        $this->assertTrue(isset($obj->key), 'Failed on 0');

        $obj->set('key', false);
        $this->assertTrue(isset($obj->key), 'Failed on false');

        // null should return false
        $obj->set('key', null);
        $this->assertFalse(isset($obj->key), 'Failed on null');
    }

    public function testGetAllKeys()
    {
        $obj = new ParseObject('TestClass');
        $obj->set('key1', 'value1');
        $obj->set('key2', 'value2');
        $obj->set('key3', 'value3');

        $estimatedData = $obj->getAllKeys();

        $this->assertEquals([
            'key1'  => 'value1',
            'key2'  => 'value2',
            'key3'  => 'value3'
        ], $estimatedData);
    }

    /**
     * @group dirty-children
     */
    public function testDirtyChildren()
    {
        $obj = new ParseObject('TestClass');
        $obj->set('key1', 'value1');
        $obj->save();

        $obj2 = new ParseObject('TestClass');
        $obj2->set('key2', 'value2');

        $this->assertFalse($obj->isDirty());

        $obj->set('innerObject', $obj2);
        $this->assertTrue($obj->isDirty());

        $this->assertTrue($obj2->isDirty());

        $obj->save();
        $this->assertFalse($obj->isDirty());
        $this->assertFalse($obj2->isDirty());


        // update the child again
        $obj2->set('key2', 'an unsaved value');
        $this->assertTrue($obj->isDirty());
        $obj->save();


        // test setting a child in child
        $obj3 = new ParseObject('TestClass');
        $obj3->set('key2', 'child of child');
        $obj2->set('innerObject', $obj3);

        $this->assertTrue($obj->isDirty());

        $obj2->save();
        $this->assertFalse($obj->isDirty());

        $obj3->set('key2', 'an unsaved value 2');
        $this->assertTrue($obj->isDirty());


        // test setting a child in child in child!
        $obj4 = new ParseObject('TestClass');
        $obj4->set('key2', 'child of child of child!');
        $obj3->set('innerObject', $obj4);

        $this->assertTrue($obj->isDirty());

        $obj3->save();
        $this->assertFalse($obj->isDirty());

        $obj4->set('key2', 'an unsaved value 3');
        $this->assertTrue($obj->isDirty());

        $obj->destroy();
        $obj2->destroy();
        $obj3->destroy();
    }

    public function testSetNullKey()
    {
        $this->setExpectedException(
            '\Exception',
            'key may not be null.'
        );
        $obj = new ParseObject('TestClass');
        $obj->set(null, 'value');
    }

    public function testSetWithArrayValue()
    {
        $this->setExpectedException(
            '\Exception',
            'Must use setArray() or setAssociativeArray() for this value.'
        );
        $obj = new ParseObject('TestClass');
        $obj->set('key', ['is-an-array' => 'yes']);
    }

    public function testSetArrayNullKey()
    {
        $this->setExpectedException(
            '\Exception',
            'key may not be null.'
        );
        $obj = new ParseObject('TestClass');
        $obj->setArray(null, ['is-an-array' => 'yes']);
    }

    public function testSetArrayWithNonArrayValue()
    {
        $this->setExpectedException(
            '\Exception',
            'Must use set() for non-array values.'
        );
        $obj = new ParseObject('TestClass');
        $obj->setArray('key', 'not-an-array');
    }

    public function testAsocSetArrayNullKey()
    {
        $this->setExpectedException(
            '\Exception',
            'key may not be null.'
        );
        $obj = new ParseObject('TestClass');
        $obj->setAssociativeArray(null, ['is-an-array' => 'yes']);
    }

    public function testAsocSetArrayWithNonArrayValue()
    {
        $this->setExpectedException(
            '\Exception',
            'Must use set() for non-array values.'
        );
        $obj = new ParseObject('TestClass');
        $obj->setAssociativeArray('key', 'not-an-array');
    }

    public function testRemovingNullKey()
    {
        $this->setExpectedException(
            '\Exception',
            'key may not be null.'
        );
        $obj = new ParseObject('TestClass');
        $obj->remove(null, 'value');
    }

    public function testRevert()
    {
        $obj = new ParseObject('TestClass');
        $obj->set('key1', 'value1');
        $obj->set('key2', 'value2');

        $obj->revert();

        $this->assertNull($obj->key1);
        $this->assertNull($obj->key2);
    }

    public function testEmptyFetchAll()
    {
        $this->assertEmpty(ParseObject::fetchAll([]));
    }

    public function testFetchAllMixedClasses()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'All objects should be of the same class.'
        );

        $objs = [];
        $obj = new ParseObject('TestClass1');
        $obj->save();
        $objs[] = $obj;

        $obj = new ParseObject('TestClass2');
        $obj->save();
        $objs[] = $obj;

        ParseObject::fetchAll($objs);
    }

    public function testFetchAllUnsavedWithoutId()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'All objects must have an ID.'
        );

        $objs = [];
        $objs[] = new ParseObject('TestClass');
        $objs[] = new ParseObject('TestClass');

        ParseObject::fetchAll($objs);
    }

    public function testFetchAllUnsavedWithId()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'All objects must exist on the server.'
        );

        $objs = [];
        $objs[] = new ParseObject('TestClass', 'objectid1');
        $objs[] = new ParseObject('TestClass', 'objectid2');

        ParseObject::fetchAll($objs);
    }

    public function testRevertingUnsavedChangesViaFetch()
    {
        $obj = new ParseObject('TestClass');
        $obj->set('montymxb', 'phpguy');
        $obj->save();

        $obj->set('montymxb', 'luaguy');

        $obj->fetch();

        $this->assertEquals('phpguy', $obj->montymxb);

        $obj->destroy();
    }

    public function testMergeFromServer()
    {
        $obj = new ParseObject('TestClass');
        $obj->set('key', 'value');
        $obj->save();

        $obj->_mergeAfterFetch([
            '__type'    => 'className',
            'key'       => 'new value',
            'ACL'       => [
                'u1'        => [
                    'write'     => false,
                    'read'      => true
                ]
            ]
        ]);

        $this->assertNull($obj->get('__type'));
        $this->assertEquals('new value', $obj->get('key'));

        $obj->destroy();
    }

    public function testDestroyingUnsaved()
    {
        $obj = new ParseObject('TestClass');
        $obj->destroy();
    }

    public function testEncodeWithArray()
    {
        $obj = new ParseObject('TestClass');
        $obj->setArray('arraykey', ['value1','value2']);

        $encoded = json_decode($obj->_encode(), true);
        $this->assertEquals($encoded['arraykey'], ['value1','value2']);
    }

    public function testToPointerWithoutId()
    {
        $this->setExpectedException(
            '\Exception',
            "Can't serialize an unsaved ParseObject"
        );
        (new ParseObject('TestClass'))->_toPointer();
    }

    public function testGettingSharedACL()
    {
        $acl = new ParseACL();
        $acl->_setShared(true);

        $obj = new ParseObject('TestClass');
        $obj->setACL($acl);

        $copy = $obj->getACL();

        $this->assertTrue($copy !== $acl);
        $this->assertEquals($copy->_encode(), $acl->_encode());
    }

    public function testSubclassRegisterMissingParseClassName()
    {
        $this->setExpectedException(
            '\Exception',
            'Cannot register a subclass that does not have a parseClassName'
        );
        ParseObjectMock::registerSubclass();
    }

    public function testGetRegisteredSubclass()
    {
        $subclass = ParseObject::getRegisteredSubclass('_User');
        $this->assertEquals('Parse\ParseUser', $subclass);

        $subclass = ParseObject::getRegisteredSubclass('Unknown');
        $this->assertTrue($subclass instanceof ParseObject);
        $this->assertEquals('Unknown', $subclass->getClassName());
    }

    public function testGettingQueryForUnregisteredSubclass()
    {
        $this->setExpectedException(
            '\Exception',
            'Cannot create a query for an unregistered subclass.'
        );
        ParseObjectMock::query();
    }

    /**
     * @group encode-encodable
     */
    public function testEncodeEncodable()
    {
        $obj = new ParseObject('TestClass');
        // set an Encodable value
        $encodable1 = new SetOperation(['key'=>'value']);
        $obj->set('key1', $encodable1);

        // set an Encodable array value
        $encodable2 = new SetOperation(['anotherkey'=>'anothervalue']);
        $obj->setArray('key2', [$encodable2]);

        $encoded = json_decode($obj->_encode(), true);

        $this->assertEquals($encoded['key1'], $encodable1->_encode());
        $this->assertEquals($encoded['key2'][0], $encodable2->_encode());
    }
}

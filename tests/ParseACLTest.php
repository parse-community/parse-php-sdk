<?php

use Parse\ParseACL;
use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseUser;

require_once 'ParseTestHelper.php';

class ParseACLTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        ParseTestHelper::setUp();
    }

    public function setUp()
    {
        ParseTestHelper::clearClass("_User");
        ParseTestHelper::clearClass("Object");
    }

    public function tearDown()
    {
        ParseTestHelper::tearDown();
    }

    public function testACLAnObjectOwnedByOneUser()
    {
        $user = new ParseUser();
        $user->setUsername('alice');
        $user->setPassword('wonderland');
        $user->signUp();
        $object = ParseObject::create('Object');
        $acl = ParseACL::createACLWithUser($user);
        $object->setACL($acl);
        $object->save();
        $this->assertTrue($object->getACL()->getUserReadAccess($user));
        $this->assertTrue($object->getACL()->getUserWriteAccess($user));
        $this->assertFalse($object->getACL()->getPublicReadAccess());
        $this->assertFalse($object->getACL()->getPublicWriteAccess());

        $user->logOut();
        $query = new ParseQuery('Object');
        try {
            $query->get($object->getObjectId());
            $this->fail('public should be unable to get');
        } catch (\Parse\ParseException $e) {
        }

        $this->assertEquals(0, count($query->find()));
        $object->set('foo', 'bar');
        try {
            $object->save();
            $this->fail('update should fail with object not found');
        } catch (\Parse\ParseException $e) {
        }

        try {
            $object->destroy();
            $this->fail('delete should fail with object not found');
        } catch (\Parse\ParseException $e) {
        }

        ParseUser::logIn('alice', 'wonderland');

        $result = $query->get($object->getObjectId());
        $this->assertNotNull($result);
        $this->assertTrue($result->getACL()->getUserReadAccess($user));
        $this->assertTrue($result->getACL()->getUserWriteAccess($user));
        $this->assertFalse($result->getACL()->getPublicReadAccess());
        $this->assertFalse($result->getACL()->getPublicWriteAccess());

        $this->assertEquals(1, count($query->find()));
        $object->save();
        $object->destroy();
    }

    public function testACLMakingAnObjectPubliclyReadable()
    {
        $user = new ParseUser();
        $user->setUsername('alice');
        $user->setPassword('wonderland');
        $user->signUp();
        $object = ParseObject::create('Object');
        $acl = ParseACL::createACLWithUser($user);
        $object->setACL($acl);
        $object->save();
        $this->assertTrue($object->getACL()->getUserReadAccess($user));
        $this->assertTrue($object->getACL()->getUserWriteAccess($user));
        $this->assertFalse($object->getACL()->getPublicReadAccess());
        $this->assertFalse($object->getACL()->getPublicWriteAccess());

        $acl->setPublicReadAccess(true);
        $object->setACL($acl);
        $object->save();

        $this->assertTrue($object->getACL()->getUserReadAccess($user));
        $this->assertTrue($object->getACL()->getUserWriteAccess($user));
        $this->assertTrue($object->getACL()->getPublicReadAccess());
        $this->assertFalse($object->getACL()->getPublicWriteAccess());

        $user->logOut();
        $query = new ParseQuery('Object');
        $result = $query->get($object->getObjectId());
        $this->assertNotNull($result);

        $this->assertTrue($result->getACL()->getUserReadAccess($user));
        $this->assertTrue($result->getACL()->getUserWriteAccess($user));
        $this->assertTrue($result->getACL()->getPublicReadAccess());
        $this->assertFalse($result->getACL()->getPublicWriteAccess());
        $this->assertEquals(1, count($query->find()));
        $object->set('foo', 'bar');
        try {
            $object->save();
            $this->fail('update should fail with object not found');
        } catch (\Parse\ParseException $e) {
        }

        try {
            $object->destroy();
            $this->fail('delete should fail with object not found');
        } catch (\Parse\ParseException $e) {
        }
    }

    public function testACLMakingAnObjectPubliclyWritable()
    {
        $user = new ParseUser();
        $user->setUsername('alice');
        $user->setPassword('wonderland');
        $user->signUp();
        $object = ParseObject::create('Object');
        $acl = ParseACL::createACLWithUser($user);
        $object->setACL($acl);
        $object->save();
        $this->assertTrue($object->getACL()->getUserReadAccess($user));
        $this->assertTrue($object->getACL()->getUserWriteAccess($user));
        $this->assertFalse($object->getACL()->getPublicReadAccess());
        $this->assertFalse($object->getACL()->getPublicWriteAccess());

        $acl->setPublicWriteAccess(true);
        $object->setACL($acl);
        $object->save();

        $this->assertTrue($object->getACL()->getUserReadAccess($user));
        $this->assertTrue($object->getACL()->getUserWriteAccess($user));
        $this->assertFalse($object->getACL()->getPublicReadAccess());
        $this->assertTrue($object->getACL()->getPublicWriteAccess());

        $user->logOut();

        $query = new ParseQuery('Object');
        try {
            $query->get($object->getObjectId());
            $this->fail('public should be unable to get');
        } catch (\Parse\ParseException $e) {
        }

        $this->assertEquals(0, count($query->find()));
        $object->set('foo', 'bar');

        $object->save();
        $object->destroy();
    }

    public function testACLSharingWithAnotherUser()
    {
        $bob = new ParseUser();
        $bob->setUsername('bob');
        $bob->setPassword('pass');
        $bob->signUp();
        $bob->logOut();

        $alice = new ParseUser();
        $alice->setUsername('alice');
        $alice->setPassword('wonderland');
        $alice->signUp();
        $object = ParseObject::create('Object');
        $acl = ParseACL::createACLWithUser($alice);
        $acl->setUserReadAccess($bob, true);
        $acl->setUserWriteAccess($bob, true);
        $object->setACL($acl);
        $object->save();
        $this->assertTrue($object->getACL()->getUserReadAccess($alice));
        $this->assertTrue($object->getACL()->getUserWriteAccess($alice));
        $this->assertTrue($object->getACL()->getUserReadAccess($bob));
        $this->assertTrue($object->getACL()->getUserWriteAccess($bob));
        $this->assertFalse($object->getACL()->getPublicReadAccess());
        $this->assertFalse($object->getACL()->getPublicWriteAccess());

        ParseUser::logOut();

        $query = new ParseQuery('Object');
        try {
            $query->get($object->getObjectId());
            $this->fail('public should be unable to get');
        } catch (\Parse\ParseException $e) {
        }

        $this->assertEquals(0, count($query->find()));
        $object->set('foo', 'bar');
        try {
            $object->save();
            $this->fail('update should fail with object not found');
        } catch (\Parse\ParseException $e) {
        }

        try {
            $object->destroy();
            $this->fail('delete should fail with object not found');
        } catch (\Parse\ParseException $e) {
        }

        ParseUser::logIn('bob', 'pass');

        $query = new ParseQuery('Object');
        $result = $query->get($object->getObjectId());
        $this->assertNotNull($result);
        $this->assertTrue($result->getACL()->getUserReadAccess($alice));
        $this->assertTrue($result->getACL()->getUserWriteAccess($alice));
        $this->assertTrue($result->getACL()->getUserReadAccess($bob));
        $this->assertTrue($result->getACL()->getUserWriteAccess($bob));
        $this->assertFalse($result->getACL()->getPublicReadAccess());
        $this->assertFalse($result->getACL()->getPublicWriteAccess());
        $this->assertEquals(1, count($query->find()));
        $object->set('foo', 'bar');
        $object->save();
        $object->destroy();
    }

    public function testACLSaveAllWithPermissions()
    {
        $alice = new ParseUser();
        $alice->setUsername('alice');
        $alice->setPassword('wonderland');
        $alice->signUp();
        $acl = ParseACL::createACLWithUser($alice);
        $object1 = ParseObject::create('Object');
        $object1->setACL($acl);
        $object1->save();
        $object2 = ParseObject::create('Object');
        $object2->setACL($acl);
        $object2->save();

        $this->assertTrue($object1->getACL()->getUserReadAccess($alice));
        $this->assertTrue($object1->getACL()->getUserWriteAccess($alice));
        $this->assertFalse($object1->getACL()->getPublicReadAccess());
        $this->assertFalse($object1->getACL()->getPublicWriteAccess());
        $this->assertTrue($object2->getACL()->getUserReadAccess($alice));
        $this->assertTrue($object2->getACL()->getUserWriteAccess($alice));
        $this->assertFalse($object2->getACL()->getPublicReadAccess());
        $this->assertFalse($object2->getACL()->getPublicWriteAccess());

        $object1->set('foo', 'bar');
        $object2->set('foo', 'bar');
        ParseObject::saveAll([$object1, $object2]);

        $query = new ParseQuery('Object');
        $query->equalTo('foo', 'bar');
        $this->assertEquals(2, count($query->find()));
    }

    public function testACLModifyingAfterLoad()
    {
        $user = new ParseUser();
        $user->setUsername('alice');
        $user->setPassword('wonderland');
        $user->signUp();
        $object = ParseObject::create('Object');
        $acl = ParseACL::createACLWithUser($user);
        $object->setACL($acl);
        $object->save();
        $this->assertTrue($object->getACL()->getUserReadAccess($user));
        $this->assertTrue($object->getACL()->getUserWriteAccess($user));
        $this->assertFalse($object->getACL()->getPublicReadAccess());
        $this->assertFalse($object->getACL()->getPublicWriteAccess());
        $query = new ParseQuery('Object');
        $objectAgain = $query->get($object->getObjectId());
        $objectAgain->getACL()->setPublicReadAccess(true);

        $this->assertTrue($objectAgain->getACL()->getUserReadAccess($user));
        $this->assertTrue($objectAgain->getACL()->getUserWriteAccess($user));
        $this->assertTrue($objectAgain->getACL()->getPublicReadAccess());
        $this->assertFalse($objectAgain->getACL()->getPublicWriteAccess());
    }

    public function testACLRequiresObjectId()
    {
        $acl = new ParseACL();
        try {
            $acl->setReadAccess(null, true);
            $this->fail('Exception should have thrown');
        } catch (Exception $e) {
        }
        try {
            $acl->getReadAccess(null);
            $this->fail('Exception should have thrown');
        } catch (Exception $e) {
        }
        try {
            $acl->setWriteAccess(null, true);
            $this->fail('Exception should have thrown');
        } catch (Exception $e) {
        }
        try {
            $acl->getWriteAccess(null);
            $this->fail('Exception should have thrown');
        } catch (Exception $e) {
        }

        $user = new ParseUser();
        try {
            $acl->setReadAccess($user, true);
            $this->fail('Exception should have thrown');
        } catch (Exception $e) {
        }
        try {
            $acl->getReadAccess($user);
            $this->fail('Exception should have thrown');
        } catch (Exception $e) {
        }
        try {
            $acl->setWriteAccess($user, true);
            $this->fail('Exception should have thrown');
        } catch (Exception $e) {
        }
        try {
            $acl->getWriteAccess($user);
            $this->fail('Exception should have thrown');
        } catch (Exception $e) {
        }
    }

    public function testIncludedObjectsGetACLs()
    {
        ParseTestHelper::clearClass("Test");
        ParseTestHelper::clearClass("Related");
        $object = ParseObject::create('Test');
        $acl = new ParseACL();
        $acl->setPublicReadAccess(true);
        $object->setACL($acl);
        $object->save();
        $this->assertTrue($object->getACL()->getPublicReadAccess());

        $related = ParseObject::create('Related');
        $related->set('test', $object);
        $related->save();

        $query = new ParseQuery('Related');
        $query->includeKey('test');
        $objectAgain = $query->first()->get('test');

        $this->assertTrue($objectAgain->getACL()->getPublicReadAccess());
        $this->assertFalse($objectAgain->getACL()->getPublicWriteAccess());
    }

    public function testIncludedObjectsGetACLWithDefaultACL()
    {
        ParseTestHelper::clearClass("Test");
        ParseTestHelper::clearClass("Related");
        $defaultACL = new ParseACL();
        $defaultACL->setPublicReadAccess(true);
        $defaultACL->setPublicWriteAccess(true);
        ParseACL::setDefaultACL($defaultACL, true);

        $object = ParseObject::create('Test');
        $acl = new ParseACL();
        $acl->setPublicReadAccess(true);
        $object->setACL($acl);
        $object->save();

        $this->assertTrue($object->getACL()->getPublicReadAccess());
        $related = ParseObject::create('Related');
        $related->set('test', $object);
        $related->save();

        $query = new ParseQuery('Related');
        $query->includeKey('test');
        $objectAgain = $query->first()->get('test');
        $this->assertTrue($objectAgain->getACL()->getPublicReadAccess());
        $this->assertFalse($objectAgain->getACL()->getPublicWriteAccess());
    }
}

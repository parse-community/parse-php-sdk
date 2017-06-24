<?php

namespace Parse\Test;

use Exception;
use Parse\ParseACL;
use Parse\ParseException;
use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseRole;
use Parse\ParseUser;

class ParseACLTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Helper::setUp();
    }

    public function setUp()
    {
        Helper::clearClass('_User');
        Helper::clearClass('Object');
    }

    public function tearDown()
    {
        Helper::tearDown();
    }

    public function testIsSharedDefault()
    {
        $acl = new ParseACL();
        $this->assertFalse($acl->_isShared());
    }

    /**
     * @group acl-one-user
     */
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
        } catch (ParseException $e) {
        }

        $this->assertEquals(0, count($query->find()));
        $object->set('foo', 'bar');
        try {
            $object->save();
            $this->fail('update should fail with object not found');
        } catch (ParseException $e) {
        }

        try {
            $object->destroy();
            $this->fail('delete should fail with object not found');
        } catch (ParseException $e) {
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

        ParseUser::logOut();
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
        } catch (ParseException $e) {
        }

        try {
            $object->destroy();
            $this->fail('delete should fail with object not found');
        } catch (ParseException $e) {
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
        } catch (ParseException $e) {
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
        } catch (ParseException $e) {
        }

        $this->assertEquals(0, count($query->find()));
        $object->set('foo', 'bar');
        try {
            $object->save();
            $this->fail('update should fail with object not found');
        } catch (ParseException $e) {
        }

        try {
            $object->destroy();
            $this->fail('delete should fail with object not found');
        } catch (ParseException $e) {
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

        ParseUser::logOut();
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

        ParseUser::logOut();
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

        ParseUser::logOut();
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
        Helper::clearClass('Test');
        Helper::clearClass('Related');
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

    public function testNullDefaultACL()
    {
        // verify null acl returns itself
        ParseACL::setDefaultACL(null, true);
        $this->assertNull(ParseACL::_getDefaultACL());
    }

    /**
     * @group default-acls
     */
    public function testDefaultACL()
    {
        // setup default acl
        $defaultACL = new ParseACL();
        $defaultACL->setPublicReadAccess(false);
        $defaultACL->setPublicWriteAccess(false);
        ParseACL::setDefaultACL($defaultACL, true);

        // get without current user
        $acl = ParseACL::_getDefaultACL();

        // verify shared
        $this->assertTrue($acl->_isShared());

        // verify empty
        $this->assertEquals(new \stdClass(), $acl->_encode());

        // login as new user
        $user = new ParseUser();
        $user->setUsername('random-username');
        $user->setPassword('random-password');
        $user->signUp();

        // verify user does not have access to original acl
        $this->assertFalse($defaultACL->getUserReadAccess($user));
        $this->assertFalse($defaultACL->getUserWriteAccess($user));

        // get default acl with user
        $acl = ParseACL::_getDefaultACL();

        // verify this user has read/write access to the returned default
        $this->assertTrue($acl->getUserReadAccess($user));
        $this->assertTrue($acl->getUserWriteAccess($user));

        ParseUser::logOut();
        $user->destroy(true);
    }

    public function testIncludedObjectsGetACLWithDefaultACL()
    {
        Helper::clearClass('Test');
        Helper::clearClass('Related');
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

    /**
     * @group acl-invalid
     */
    public function testCreatingACLWithInvalidId()
    {
        $this->setExpectedException(
            '\Exception',
            'Tried to create an ACL with an invalid userId.'
        );

        ParseACL::_createACLFromJSON([
            1234    => 'write'
        ]);
    }

    /**
     * @group acl-invalid
     */
    public function testCreatingWithBadAccessType()
    {
        $this->setExpectedException(
            '\Exception',
            'Tried to create an ACL with an invalid permission type.'
        );

        ParseACL::_createACLFromJSON([
            'id'    => [
                'not-valid' => true
            ]
        ]);
    }

    /**
     * @group acl-invalid
     */
    public function testCreatingWithInvalidPermissionValue()
    {
        $this->setExpectedException(
            '\Exception',
            'Tried to create an ACL with an invalid permission value.'
        );

        ParseACL::_createACLFromJSON([
            'id'    => [
                'write' => 'not-valid'
            ]
        ]);
    }

    /**
     * @group acl-user-notallowed
     */
    public function testSettingPermissionForUserNotAllowed()
    {
        // add 'userid'
        $acl = new ParseACL();
        $acl->setPublicReadAccess(false);
        $acl->setPublicWriteAccess(false);
        $acl->setReadAccess('userid', true);

        // verify this user can read
        $this->assertTrue($acl->getReadAccess('userid'));

        // attempt to add another id with false access
        $acl->setReadAccess('anotheruserid', false);

        // verify the second id was not actually added internally
        $permissions = $acl->_encode();
        $this->assertEquals([
            'userid'    => [
                'read' => true
            ]
        ], $permissions);
    }

    /**
     * @group removing-from-acl
     */
    public function testRemovingFromAcl()
    {
        // add 'userid'
        $acl = new ParseACL();
        $acl->setPublicReadAccess(false);
        $acl->setPublicWriteAccess(false);
        $acl->setReadAccess('userid', true);
        $acl->setWriteAccess('userid', true);

        // verify this user can read
        $this->assertTrue($acl->getReadAccess('userid'));

        // remove read access
        $acl->setReadAccess('userid', false);

        // verify this user cannot read
        $this->assertFalse($acl->getReadAccess('userid'));

        // verify user can still write
        $this->assertTrue($acl->getWriteAccess('userid'));

        // remove write access
        $acl->setWriteAccess('userid', false);

        // verify user can no longer write
        $this->assertFalse($acl->getWriteAccess('userid'));

        // verify acl is now empty, should be an instance of stdClass
        $permissions = $acl->_encode();
        $this->assertEquals(new \stdClass(), $permissions, 'ACL not empty after removing last user.');
    }

    public function testSettingUserReadAccessWithoutId()
    {
        $this->setExpectedException(
            '\Exception',
            'cannot setReadAccess for a user with null id'
        );

        $acl = new ParseACL();
        $acl->setUserReadAccess(new ParseUser(), true);
    }

    public function testGettingUserReadAccessWithoutId()
    {
        $this->setExpectedException(
            '\Exception',
            'cannot getReadAccess for a user with null id'
        );

        $acl = new ParseACL();
        $acl->getUserReadAccess(new ParseUser());
    }

    public function testSettingUserWriteAccessWithoutId()
    {
        $this->setExpectedException(
            '\Exception',
            'cannot setWriteAccess for a user with null id'
        );

        $acl = new ParseACL();
        $acl->setUserWriteAccess(new ParseUser(), true);
    }

    public function testGettingUserWriteAccessWithoutId()
    {
        $this->setExpectedException(
            '\Exception',
            'cannot getWriteAccess for a user with null id'
        );

        $acl = new ParseACL();
        $acl->getUserWriteAccess(new ParseUser());
    }

    /**
     * @group test-role-access
     */
    public function testRoleAccess()
    {
        $acl = new ParseACL();

        // Create a role
        $roleAcl = new ParseACL();
        $role = ParseRole::createRole('BasicRole', $roleAcl);
        $role->save();

        // Read Access
        $this->assertFalse($acl->getRoleReadAccess($role));

        // set true
        $acl->setRoleReadAccess($role, true);
        $this->assertTrue($acl->getRoleReadAccess($role));

        // set back to false
        $acl->setRoleReadAccess($role, false);
        $this->assertFalse($acl->getRoleReadAccess($role));

        // Write Access
        $this->assertFalse($acl->getRoleWriteAccess($role));

        // set true
        $acl->setRoleWriteAccess($role, true);
        $this->assertTrue($acl->getRoleWriteAccess($role));

        // set back to false
        $acl->setRoleWriteAccess($role, false);
        $this->assertFalse($acl->getRoleWriteAccess($role));

        $role->destroy(true);
    }

    public function testUnsavedRoleAdded()
    {
        $this->setExpectedException(
            '\Exception',
            'Roles must be saved to the server before they can be used in an ACL.'
        );

        $acl = new ParseACL();
        $acl->setRoleReadAccess(new ParseRole(), true);
    }

    public function testRoleAccessWithName()
    {
        $acl = new ParseACL();
        // Read Access
        $this->assertFalse($acl->getRoleReadAccessWithName('role'));

        // set true
        $acl->setRoleReadAccessWithName('role', true);
        $this->assertTrue($acl->getRoleReadAccessWithName('role'));

        // set back to false
        $acl->setRoleReadAccessWithName('role', false);
        $this->assertFalse($acl->getRoleReadAccessWithName('role'));

        // Write Access
        $this->assertFalse($acl->getRoleWriteAccessWithName('role'));

        // set true
        $acl->setRoleWriteAccessWithName('role', true);
        $this->assertTrue($acl->getRoleWriteAccessWithName('role'));

        // set back to false
        $acl->setRoleWriteAccessWithName('role', false);
        $this->assertFalse($acl->getRoleWriteAccessWithName('role'));
    }
}

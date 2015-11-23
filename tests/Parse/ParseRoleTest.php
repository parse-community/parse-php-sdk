<?php

namespace Parse\Test;

use Parse\ParseACL;
use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseRole;
use Parse\ParseUser;

class ParseRoleTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Helper::setUp();
    }

    public function setUp()
    {
        Helper::clearClass('_User');
        Helper::clearClass('_Role');
        Helper::clearClass('Things');
    }

    public function tearDown()
    {
        Helper::tearDown();
    }

    public function testCreateRole()
    {
        $role = ParseRole::createRole('Admin', $this->aclPublic());
        $role->save();
        $this->assertNotNull($role->getObjectId(), 'Role should have objectId.');
    }

    public function testRoleWithoutACLFails()
    {
        $role = new ParseRole();
        $role->setName('Admin');
        $this->setExpectedException('Parse\ParseException', 'ACL');
        $role->save();
    }

    public function testNameValidation()
    {
        $role = ParseRole::createRole('Admin', $this->aclPublic());
        $this->assertEquals('Admin', $role->getName());
        $role->setName('Superuser');
        $this->assertEquals('Superuser', $role->getName());
        $role->setName('Super-Users');
        $this->assertEquals('Super-Users', $role->getName());
        $role->setName('A1234');
        $this->assertEquals('A1234', $role->getName());
        $role->save();
        $this->setExpectedException('Parse\ParseException', 'has been saved');
        $role->setName('Moderators');
    }

    public function testGetCreatedRole()
    {
        $role = ParseRole::createRole('Admin', $this->aclPublic());
        $role->save();
        $query = ParseRole::query();
        $obj = $query->get($role->getObjectId());
        $this->assertTrue($obj instanceof ParseRole);
        $this->assertEquals($role->getObjectId(), $obj->getObjectId());
    }

    public function testFindRolesByName()
    {
        $admin = ParseRole::createRole('Admin', $this->aclPublic());
        $mod = ParseRole::createRole('Moderator', $this->aclPublic());
        ParseObject::saveAll([$admin, $mod]);
        $query1 = ParseRole::query();
        $query1->equalTo('name', 'Admin');
        $this->assertEquals(1, $query1->count(), 'Count should be 1.');
        $query2 = ParseRole::query();
        $query2->equalTo('name', 'Moderator');
        $this->assertEquals(1, $query2->count(), 'Count should be 1.');
        $query3 = ParseRole::query();
        $this->assertEquals(2, $query3->count());
    }

    public function testRoleNameUnique()
    {
        $role = ParseRole::createRole('Admin', $this->aclPublic());
        $role->save();
        $role2 = ParseRole::createRole('Admin', $this->aclPublic());
        $this->setExpectedException('Parse\ParseException', 'duplicate');
        $role2->save();
    }

    public function testExplicitRoleACL()
    {
        $eden = $this->createEden();
        ParseUser::logIn('adam', 'adam');
        $query = new ParseQuery('Things');
        $apple = $query->get($eden['apple']->getObjectId());
        ParseUser::logIn('eve', 'eve');
        $apple = $query->get($eden['apple']->getObjectId());
        ParseUser::logIn('snake', 'snake');
        $this->setExpectedException('Parse\ParseException', 'not found');
        $apple = $query->get($eden['apple']->getObjectId());
    }

    public function testRoleHierarchyAndPropagation()
    {
        $eden = $this->createEden();
        ParseUser::logIn('adam', 'adam');
        $query = new ParseQuery('Things');
        $garden = $query->get($eden['garden']->getObjectId());
        ParseUser::logIn('eve', 'eve');
        $garden = $query->get($eden['garden']->getObjectId());
        ParseUser::logIn('snake', 'snake');
        $garden = $query->get($eden['garden']->getObjectId());

        $eden['edenkin']->getRoles()->remove($eden['humans']);
        $eden['edenkin']->save();
        ParseUser::logIn('adam', 'adam');
        try {
            $query->get($eden['garden']->getObjectId());
            $this->fail('Get should have failed.');
        } catch (\Parse\ParseException $ex) {
            if ($ex->getMessage() != 'Object not found.') {
                throw $ex;
            }
        }
        ParseUser::logIn('eve', 'eve');
        try {
            $query->get($eden['garden']->getObjectId());
            $this->fail('Get should have failed.');
        } catch (\Parse\ParseException $ex) {
            if ($ex->getMessage() != 'Object not found.') {
                throw $ex;
            }
        }
        ParseUser::logIn('snake', 'snake');
        $query->get($eden['garden']->getObjectId());
    }

    public function testAddUserAfterFetch()
    {
        $user = new ParseUser();
        $user->setUsername('bob');
        $user->setPassword('barker');
        $user->signUp();
        $role = ParseRole::createRole('MyRole', ParseACL::createACLWithUser($user));
        $role->save();
        $query = ParseRole::query();
        $roleAgain = $query->get($role->getObjectId());
        $roleAgain->getUsers()->add($user);
        $roleAgain->save();
    }

    /**
     * Utilities.
     */
    public function aclPrivateTo($someone)
    {
        $acl = new ParseACL();
        $acl->setReadAccess($someone, true);
        $acl->setWriteAccess($someone, true);

        return $acl;
    }

    public function aclPublic()
    {
        $acl = new ParseACL();
        $acl->setPublicReadAccess(true);
        $acl->setPublicWriteAccess(true);

        return $acl;
    }

    public function createUser($username)
    {
        $user = new ParseUser();
        $user->setUsername($username);
        $user->setPassword($username);

        return $user;
    }

    public function createEden()
    {
        $eden = [];
        $eden['adam'] = $this->createUser('adam');
        $eden['eve'] = $this->createUser('eve');
        $eden['snake'] = $this->createUser('snake');
        $eden['adam']->signUp();
        $eden['eve']->signUp();
        $eden['snake']->signUp();
        $eden['humans'] = ParseRole::createRole('humans', $this->aclPublic());
        $eden['humans']->getUsers()->add($eden['adam']);
        $eden['humans']->getUsers()->add($eden['eve']);
        $eden['creatures'] = ParseRole::createRole(
            'creatures',
            $this->aclPublic()
        );
        $eden['creatures']->getUsers()->add($eden['snake']);
        ParseObject::saveAll([$eden['humans'], $eden['creatures']]);
        $eden['edenkin'] = ParseRole::createRole('edenkin', $this->aclPublic());
        $eden['edenkin']->getRoles()->add($eden['humans']);
        $eden['edenkin']->getRoles()->add($eden['creatures']);
        $eden['edenkin']->save();

        $eden['apple'] = ParseObject::create('Things');
        $eden['apple']->set('name', 'apple');
        $eden['apple']->set('ACL', $this->aclPrivateTo($eden['humans']));

        $eden['garden'] = ParseObject::create('Things');
        $eden['garden']->set('name', 'garden');
        $eden['garden']->set('ACL', $this->aclPrivateTo($eden['edenkin']));

        ParseObject::saveAll([$eden['apple'], $eden['garden']]);

        return $eden;
    }
}

<?php

namespace Parse\Test;

use Parse\ParseACL;
use Parse\ParseException;
use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseRole;
use Parse\ParseUser;

use PHPUnit\Framework\TestCase;

class ParseRoleTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        Helper::setUp();
    }

    public function setup() : void
    {
        Helper::clearClass('_User');
        Helper::clearClass('_Role');
        Helper::clearClass('Things');
    }

    public function tearDown() : void
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
        $this->expectException('Parse\ParseException', 'ACL');
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
        $this->expectException('Parse\ParseException', 'has been saved');
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

    /**
     * @group role-name-duplicate
     */
    public function testRoleNameUnique()
    {
        $role = ParseRole::createRole('Admin', $this->aclPublic());
        $role->save();
        $role2 = ParseRole::createRole('Admin', $this->aclPublic());
        $this->expectException(
            'Parse\ParseException',
            "Cannot add duplicate role name of 'Admin'"
        );
        $role2->save();
    }

    /**
     * @group explicit-role-acl
     */
    public function testExplicitRoleACL()
    {
        $eden = $this->createEden();

        // verify adam can get the apple
        ParseUser::logIn('adam', 'adam');
        $query = new ParseQuery('Things');
        $query->get($eden['apple']->getObjectId());

        // verify eve can get the apple
        ParseUser::logIn('eve', 'eve');
        $query->get($eden['apple']->getObjectId());

        // verify the snake cannot get the apple
        ParseUser::logIn('snake', 'snake');
        $this->expectException('Parse\ParseException', 'not found');
        $query->get($eden['apple']->getObjectId());
    }

    public function testRoleHierarchyAndPropagation()
    {
        $eden = $this->createEden();

        // verify adam can enter the garden
        ParseUser::logIn('adam', 'adam');
        $query = new ParseQuery('Things');
        $query->get($eden['garden']->getObjectId());

        // verify adam can enter the garden
        ParseUser::logIn('eve', 'eve');
        $query->get($eden['garden']->getObjectId());

        // verify the snake can enter the garden
        ParseUser::logIn('snake', 'snake');
        $query->get($eden['garden']->getObjectId());

        // make it so humans can no longer enter the garden
        $eden['edenkin']->getRoles()->remove($eden['humans']);
        $eden['edenkin']->save();

        // verify adam can no longer enter the garden
        ParseUser::logIn('adam', 'adam');
        try {
            $query->get($eden['garden']->getObjectId());
            $this->fail('Get should have failed.');
        } catch (ParseException $ex) {
            if ($ex->getMessage() != 'Object not found.') {
                throw $ex;
            }
        }

        // verify eve can no longer enter the garden
        ParseUser::logIn('eve', 'eve');
        try {
            $query->get($eden['garden']->getObjectId());
            $this->fail('Get should have failed.');
        } catch (ParseException $ex) {
            if ($ex->getMessage() != 'Object not found.') {
                throw $ex;
            }
        }

        // verify the snake can still enter the garden
        ParseUser::logIn('snake', 'snake');
        $garden = $query->get($eden['garden']->getObjectId());
        $this->assertEquals($garden->getObjectId(), $eden['garden']->getObjectId());
        ParseUser::logOut();
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
        $users = $roleAgain->getUsers()->getQuery()->find();
        $this->assertEquals($user->getObjectId(), $users[0]->getObjectId());
        ParseUser::logOut();
    }

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

    public function testSettingNonStringAsName()
    {
        $this->expectException(
            '\Parse\ParseException',
            "A role's name must be a string."
        );
        $role = new ParseRole();
        $role->setName(12345);
    }

    /**
     * @group role-save-noname
     */
    public function testSavingWithoutName()
    {
        $this->expectException(
            '\Parse\ParseException',
            'Roles must have a name.'
        );
        $role = new ParseRole();
        $role->setACL(new ParseACL());
        $role->save();
    }
}

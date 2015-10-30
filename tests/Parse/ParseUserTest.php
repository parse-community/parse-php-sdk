<?php

namespace Parse\Test;

use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseUser;

class ParseUserTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Helper::setUp();
        Helper::clearClass(ParseUser::$parseClassName);
    }

    public function tearDown()
    {
        Helper::tearDown();
        ParseUser::logOut();
        Helper::clearClass(ParseUser::$parseClassName);
    }

    public static function tearDownAfterClass()
    {
        ParseUser::_unregisterSubclass();
    }

    public function testUserAttributes()
    {
        $user = new ParseUser();
        $user->setUsername('asdf');
        $user->setPassword('zxcv');
        $user->setEmail('asds@mail.com');
        $this->assertEquals('asdf', $user->getUsername());
        $this->assertEquals('asds@mail.com', $user->getEmail());
    }

    public function testUserSignUp()
    {
        $user = new ParseUser();
        $user->setUsername('asdf');
        $user->setPassword('zxcv');
        $user->signUp();
        $this->assertTrue($user->isAuthenticated());
    }

    public function testLoginSuccess()
    {
        $this->testUserSignUp();
        $user = ParseUser::logIn('asdf', 'zxcv');
        $this->assertTrue($user->isAuthenticated());
        $this->assertEquals('asdf', $user->get('username'));
    }

    public function testLoginEmptyUsername()
    {
        $this->setExpectedException('Parse\ParseException', 'empty name');
        $user = ParseUser::logIn('', 'bogus');
    }

    public function testLoginEmptyPassword()
    {
        $this->setExpectedException('Parse\ParseException', 'empty password');
        $user = ParseUser::logIn('asdf', '');
    }

    public function testLoginWrongUsername()
    {
        $this->setExpectedException('Parse\ParseException', 'invalid login');
        $user = ParseUser::logIn('non_existent_user', 'bogus');
    }

    public function testLoginWrongPassword()
    {
        $this->testUserSignUp();
        $this->setExpectedException('Parse\ParseException', 'invalid login');
        $user = ParseUser::logIn('asdf', 'bogus');
    }

    public function testBecome()
    {
        $user = new ParseUser();
        $user->setUsername('asdf');
        $user->setPassword('zxcv');
        $user->signUp();
        $this->assertEquals(ParseUser::getCurrentUser(), $user);

        $sessionToken = $user->getSessionToken();

        $newUser = ParseUser::become($sessionToken);
        $this->assertEquals(ParseUser::getCurrentUser(), $newUser);
        $this->assertEquals('asdf', $newUser->get('username'));

        $this->setExpectedException('Parse\ParseException', 'invalid session');
        $failUser = ParseUser::become('garbage_token');
    }

    public function testCannotSingUpAlreadyExistingUser()
    {
        $this->testUserSignUp();
        $user = ParseUser::getCurrentUser();
        $user->setPassword('zxcv');
        $this->setExpectedException('Parse\ParseException', 'already existing user');
        $user->signUp();
    }

    public function testCannotAlterOtherUser()
    {
        $user = new ParseUser();
        $user->setUsername('asdf');
        $user->setPassword('zxcv');
        $user->signUp();

        $otherUser = new ParseUser();
        $otherUser->setUsername('hacker');
        $otherUser->setPassword('password');
        $otherUser->signUp();

        $this->assertEquals(ParseUser::getCurrentUser(), $otherUser);

        $this->setExpectedException(
            'Parse\ParseException',
            'UserCannotBeAlteredWithoutSession'
        );
        $user->setUsername('changed');
        $user->save();
    }

    public function testCannotDeleteOtherUser()
    {
        $user = new ParseUser();
        $user->setUsername('asdf');
        $user->setPassword('zxcv');
        $user->signUp();

        $otherUser = new ParseUser();
        $otherUser->setUsername('hacker');
        $otherUser->setPassword('password');
        $otherUser->signUp();

        $this->assertEquals(ParseUser::getCurrentUser(), $otherUser);

        $this->setExpectedException(
            'Parse\ParseException',
            'UserCannotBeAlteredWithoutSession'
        );
        $user->destroy();
    }

    public function testCannotSaveAllWithOtherUser()
    {
        $user = new ParseUser();
        $user->setUsername('asdf');
        $user->setPassword('zxcv');
        $user->signUp();

        $otherUser = new ParseUser();
        $otherUser->setUsername('hacker');
        $otherUser->setPassword('password');
        $otherUser->signUp();

        $this->assertEquals(ParseUser::getCurrentUser(), $otherUser);

        $obj = ParseObject::create('TestObject');
        $obj->set('user', $otherUser);
        $obj->save();

        $item1 = ParseObject::create('TestObject');
        $item1->set('num', 0);
        $item1->save();

        $item1->set('num', 1);
        $item2 = ParseObject::create('TestObject');
        $item2->set('num', 2);
        $user->setUsername('changed');
        $this->setExpectedException(
            'Parse\ParseAggregateException',
            'Errors during batch save.'
        );
        ParseObject::saveAll([$item1, $item2, $user]);
    }

    public function testCurrentUser()
    {
        $user = new ParseUser();
        $user->setUsername('asdf');
        $user->setPassword('zxcv');
        $user->signUp();

        $current = ParseUser::getCurrentUser();
        $this->assertEquals($current->getObjectId(), $user->getObjectId());
        $this->assertNotNull($user->getSessionToken());

        $currentAgain = ParseUser::getCurrentUser();
        $this->assertEquals($current, $currentAgain);

        ParseUser::logOut();
        $this->assertNull(ParseUser::getCurrentUser());
    }

    public function testIsCurrent()
    {
        $user1 = new ParseUser();
        $user2 = new ParseUser();
        $user3 = new ParseUser();

        $user1->setUsername('a');
        $user2->setUsername('b');
        $user3->setUsername('c');

        $user1->setPassword('password');
        $user2->setPassword('password');
        $user3->setPassword('password');

        $user1->signUp();
        $this->assertTrue($user1->isCurrent());
        $this->assertFalse($user2->isCurrent());
        $this->assertFalse($user3->isCurrent());

        $user2->signUp();
        $this->assertTrue($user2->isCurrent());
        $this->assertFalse($user1->isCurrent());
        $this->assertFalse($user3->isCurrent());

        $user3->signUp();
        $this->assertTrue($user3->isCurrent());
        $this->assertFalse($user1->isCurrent());
        $this->assertFalse($user2->isCurrent());

        $user = ParseUser::logIn('a', 'password');
        $this->assertTrue($user1->isCurrent());
        $this->assertFalse($user2->isCurrent());
        $this->assertFalse($user3->isCurrent());

        $user = ParseUser::logIn('b', 'password');
        $this->assertTrue($user2->isCurrent());
        $this->assertFalse($user1->isCurrent());
        $this->assertFalse($user3->isCurrent());

        $user = ParseUser::logIn('c', 'password');
        $this->assertTrue($user3->isCurrent());
        $this->assertFalse($user1->isCurrent());
        $this->assertFalse($user2->isCurrent());

        ParseUser::logOut();
        $this->assertFalse($user1->isCurrent());
        $this->assertFalse($user2->isCurrent());
        $this->assertFalse($user3->isCurrent());
    }

    public function testPasswordReset()
    {
        $user = new ParseUser();
        $user->setUsername('asdf');
        $user->setPassword('zxcv');
        $user->set('email', 'asdf@example.com');
        $user->signUp();

        ParseUser::requestPasswordReset('asdf@example.com');
    }

    public function testPasswordResetFails()
    {
        $this->setExpectedException(
            'Parse\ParseException',
            'no user found with email'
        );
        ParseUser::requestPasswordReset('non_existent@example.com');
    }

    public function testUserAssociations()
    {
        $child = ParseObject::create('TestObject');
        $child->save();

        $user = new ParseUser();
        $user->setUsername('asdf');
        $user->setPassword('zxcv');
        $user->set('child', $child);
        $user->signUp();

        $object = ParseObject::create('TestObject');
        $object->set('user', $user);
        $object->save();

        $query = new ParseQuery('TestObject');
        $objectAgain = $query->get($object->getObjectId());
        $userAgain = $objectAgain->get('user');
        $userAgain->fetch();

        $this->assertEquals($userAgain->getObjectId(), $user->getObjectId());
        $this->assertEquals(
            $userAgain->get('child')->getObjectId(),
            $child->getObjectId()
        );
    }

    public function testUserQueries()
    {
        Helper::clearClass(ParseUser::$parseClassName);
        $user = new ParseUser();
        $user->setUsername('asdf');
        $user->setPassword('zxcv');
        $user->set('email', 'asdf@example.com');
        $user->signUp();

        $query = ParseUser::query();
        $users = $query->find();

        $this->assertEquals(1, count($users));
        $this->assertEquals($user->getObjectId(), $users[0]->getObjectId());
        $this->assertEquals('asdf@example.com', $users[0]->get('email'));
    }

    public function testContainedInUserArrayQueries()
    {
        Helper::clearClass(ParseUser::$parseClassName);
        Helper::clearClass('TestObject');
        $userList = [];
        for ($i = 0; $i < 4; $i++) {
            $user = new ParseUser();
            $user->setUsername('user_num_'.$i);
            $user->setPassword('password');
            $user->set('email', 'asdf_'.$i.'@example.com');
            $user->signUp();
            $userList[] = $user;
        }
        $messageList = [];
        for ($i = 0; $i < 5; $i++) {
            $message = ParseObject::create('TestObject');
            $toUser = ($i + 1) % 4;
            $fromUser = $i % 4;
            $message->set('to', $userList[$toUser]);
            $message->set('from', $userList[$fromUser]);
            $message->save();
            $messageList[] = $message;
        }

        $inList = [$userList[0], $userList[3], $userList[3]];
        $query = new ParseQuery('TestObject');
        $query->containedIn('from', $inList);
        $results = $query->find();

        $this->assertEquals(3, count($results));
    }

    public function testSavingUserThrows()
    {
        $user = new ParseUser();
        $user->setUsername('asdf');
        $user->setPassword('zxcv');
        $this->setExpectedException('Parse\ParseException', 'You must call signUp');
        $user->save();
    }

    public function testUserUpdates()
    {
        $user = new ParseUser();
        $user->setUsername('asdf');
        $user->setPassword('zxcv');
        $user->set('email', 'asdf@example.com');
        $user->signUp();
        $this->assertNotNull(ParseUser::getCurrentUser());
        $user->setUsername('test');
        $user->save();
        $this->assertNotNull($user->get('username'));
        $this->assertNotNull($user->get('email'));
        $user->destroy();

        $query = ParseUser::query();
        $this->setExpectedException('Parse\ParseException', 'Object not found.');
        $fail = $query->get($user->getObjectId(), true);
    }

    public function testCountUsers()
    {
        Helper::clearClass(ParseUser::$parseClassName);
        $ilya = new ParseUser();
        $ilya->setUsername('ilya');
        $ilya->setPassword('password');
        $ilya->signUp();

        $kevin = new ParseUser();
        $kevin->setUsername('kevin');
        $kevin->setPassword('password');
        $kevin->signUp();

        $james = new ParseUser();
        $james->setUsername('james');
        $james->setPassword('password');
        $james->signUp();

        $query = ParseUser::query();
        $result = $query->count();
        $this->assertEquals(3, $result);
    }

    public function testUserLoadedFromStorageFromSignUp()
    {
        Helper::clearClass(ParseUser::$parseClassName);
        $fosco = new ParseUser();
        $fosco->setUsername('fosco');
        $fosco->setPassword('password');
        $fosco->signUp();
        $id = $fosco->getObjectId();
        $this->assertNotNull($id);
        $current = ParseUser::getCurrentUser();
        $this->assertEquals($id, $current->getObjectId());
        ParseUser::_clearCurrentUserVariable();
        $current = ParseUser::getCurrentUser();
        $this->assertEquals($id, $current->getObjectId());
    }

    public function testUserLoadedFromStorageFromLogIn()
    {
        Helper::clearClass(ParseUser::$parseClassName);
        $fosco = new ParseUser();
        $fosco->setUsername('fosco');
        $fosco->setPassword('password');
        $fosco->signUp();
        $id = $fosco->getObjectId();
        $this->assertNotNull($id);
        ParseUser::logOut();
        ParseUser::_clearCurrentUserVariable();
        $current = ParseUser::getCurrentUser();
        $this->assertNull($current);
        ParseUser::logIn('fosco', 'password');
        $current = ParseUser::getCurrentUser();
        $this->assertEquals($id, $current->getObjectId());
        ParseUser::_clearCurrentUserVariable();
        $current = ParseUser::getCurrentUser();
        $this->assertEquals($id, $current->getObjectId());
    }

    public function testUserWithMissingUsername()
    {
        $user = new ParseUser();
        $user->setPassword('test');
        $this->setExpectedException('Parse\ParseException', 'empty name');
        $user->signUp();
    }

    public function testUserWithMissingPassword()
    {
        $user = new ParseUser();
        $user->setUsername('test');
        $this->setExpectedException('Parse\ParseException', 'empty password');
        $user->signUp();
    }

    public function testCurrentUserIsNotDirty()
    {
        $user = new ParseUser();
        $user->setUsername('asdf');
        $user->setPassword('zxcv');
        $user->set('bleep', 'bloop');
        $user->signUp();
        $this->assertFalse($user->isKeyDirty('bleep'));
        $userAgain = ParseUser::getCurrentUser();
        $this->assertFalse($userAgain->isKeyDirty('bleep'));
    }
}

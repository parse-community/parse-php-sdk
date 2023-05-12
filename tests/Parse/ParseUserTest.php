<?php

namespace Parse\Test;

use Parse\ParseCloud;
use Parse\ParseClient;
use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseUser;

use PHPUnit\Framework\TestCase;

class ParseUserTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        Helper::setUp();
        Helper::clearClass(ParseUser::$parseClassName);
    }

    public function tearDown() : void
    {
        Helper::tearDown();
        ParseUser::logOut();
        Helper::clearClass(ParseUser::$parseClassName);
    }

    public static function tearDownAfterClass() : void
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

        ParseUser::logOut();
    }

    public function testLoginEmptyUsername()
    {
        $this->expectException('Parse\ParseException', 'empty name');
        ParseUser::logIn('', 'bogus');
    }

    public function testLoginEmptyPassword()
    {
        $this->expectException('Parse\ParseException', 'empty password');
        ParseUser::logIn('asdf', '');
    }

    public function testLoginWrongUsername()
    {
        $this->expectException('Parse\ParseException', 'Invalid username/password.');
        ParseUser::logIn('non_existent_user', 'bogus');
    }

    public function testLoginWrongPassword()
    {
        $this->testUserSignUp();
        $this->expectException('Parse\ParseException', 'Invalid username/password.');
        ParseUser::logIn('asdf', 'bogus');
    }

    public function testLoginAsSuccess()
    {
        $user = new ParseUser();
        $user->setUsername('plainusername');
        $user->setPassword('plainpassword');
        $user->signUp();

        $id = $user->getObjectId();
        $loggedInUser = ParseUser::logInAs($id);
        $this->assertTrue($loggedInUser->isAuthenticated());
        $this->assertEquals('plainusername', $loggedInUser->get('username'));

        ParseUser::logOut();
    }

    public function testLoginAsEmptyUsername()
    {
        $this->expectException('Parse\ParseException', 'Cannot log in as user with an empty user id.');
        ParseUser::logInAs('');
    }

    public function testLoginAsNonexistentUser()
    {
        $this->expectException('Parse\ParseException', 'user not found.');
        ParseUser::logInAs('a1b2c3d4e5');
    }

    public function testLoginWithFacebook()
    {
        $this->expectException(
            'Parse\ParseException',
            'Facebook auth is invalid for this user.'
        );
        ParseUser::logInWithFacebook('asdf', 'zxcv');
    }

    public function testLoginWithFacebookNoId()
    {
        $this->expectException(
            'Parse\ParseException',
            'Cannot log in Facebook user without an id.'
        );
        ParseUser::logInWithFacebook(null, 'asdf');
    }

    public function testLoginWithFacebookNoAccessToken()
    {
        $this->expectException(
            'Parse\ParseException',
            'Cannot log in Facebook user without an access token.'
        );
        ParseUser::logInWithFacebook('asdf', null);
    }

    public function testLoginWithTwitter()
    {
        $this->expectException(
            'Parse\ParseException',
            'Twitter auth is invalid for this user.'
        );
        ParseUser::logInWithTwitter('asdf', 'asdf', 'asdf', null, 'bogus', 'bogus');
    }

    public function testLoginWithTwitterNoId()
    {
        $this->expectException(
            'Parse\ParseException',
            'Cannot log in Twitter user without an id.'
        );
        ParseUser::logInWithTwitter(null, 'asdf', 'asdf', null, 'bogus', 'bogus');
    }

    public function testLoginWithTwitterNoScreenName()
    {
        $this->expectException(
            'Parse\ParseException',
            'Cannot log in Twitter user without Twitter screen name.'
        );
        ParseUser::logInWithTwitter('asdf', null, 'asdf', null, 'bogus', 'bogus');
    }

    public function testLoginWithTwitterNoConsumerKey()
    {
        $this->expectException(
            'Parse\ParseException',
            'Cannot log in Twitter user without a consumer key.'
        );
        ParseUser::logInWithTwitter('asdf', 'asdf', null, null, 'bogus', 'bogus');
    }

    public function testLoginWithTwitterNoAuthToken()
    {
        $this->expectException(
            'Parse\ParseException',
            'Cannot log in Twitter user without an auth token.'
        );
        ParseUser::logInWithTwitter('asdf', 'asdf', 'asdf', null, null, 'bogus');
    }

    public function testLoginWithTwitterNoAuthTokenSecret()
    {
        $this->expectException(
            'Parse\ParseException',
            'Cannot log in Twitter user without an auth token secret.'
        );
        ParseUser::logInWithTwitter('asdf', 'asdf', 'asdf', null, 'bogus', null);
    }

    public function testLoginWithAnonymous()
    {
        $user = ParseUser::loginWithAnonymous();
        $this->assertTrue($user->isAuthenticated());
    }

    public function testLinkWithFacebook()
    {
        $this->expectException('Parse\ParseException');
        $this->testUserSignUp();
        $user = ParseUser::logIn('asdf', 'zxcv');
        $user->linkWithFacebook('asdf', 'zxcv');
    }

    public function testLinkWithFacebookUnsavedUser()
    {
        $this->expectException(
            'Parse\ParseException',
            'Cannot link an unsaved user, use ParseUser::logInWithFacebook'
        );
        $user = new ParseUser();
        $user->linkWithFacebook('asdf', 'zxcv');
    }

    public function testLinkWithFacebookNoId()
    {
        $this->expectException(
            'Parse\ParseException',
            'Cannot link Facebook user without an id.'
        );
        $this->testUserSignUp();
        $user = ParseUser::logIn('asdf', 'zxcv');
        $user->linkWithFacebook(null, 'zxcv');
    }

    public function testLinkWithFacebookNoAccessToken()
    {
        $this->expectException(
            'Parse\ParseException',
            'Cannot link Facebook user without an access token.'
        );
        $this->testUserSignUp();
        $user = ParseUser::logIn('asdf', 'zxcv');
        $user->linkWithFacebook('asdf', null);
    }

    public function testLinkWithTwitter()
    {
        $this->expectException('Parse\ParseException');
        $this->testUserSignUp();
        $user = ParseUser::logIn('asdf', 'zxcv');
        $user->linkWithTwitter('qwer', 'asdf', 'zxcv', null, 'bogus', 'bogus');
    }

    public function testLinkWithTwitterUnsavedUser()
    {
        $this->expectException(
            'Parse\ParseException',
            'Cannot link an unsaved user, use ParseUser::logInWithTwitter'
        );
        $user = new ParseUser();
        $user->linkWithTwitter('qwer', 'asdf', 'zxcv', null, 'bogus', 'bogus');
    }

    public function testLinkWithTwitterNoId()
    {
        $this->expectException(
            'Parse\ParseException',
            'Cannot link Twitter user without an id.'
        );
        $this->testUserSignUp();
        $user = ParseUser::logIn('asdf', 'zxcv');
        $user->linkWithTwitter(null, 'asdf', 'zxcv', null, 'bogus', 'bogus');
    }

    public function testLinkWithTwitterNoScreenName()
    {
        $this->expectException(
            'Parse\ParseException',
            'Cannot link Twitter user without Twitter screen name.'
        );
        $this->testUserSignUp();
        $user = ParseUser::logIn('asdf', 'zxcv');
        $user->linkWithTwitter('qwer', null, 'zxcv', null, 'bogus', 'bogus');
    }

    public function testLinkWithTwitterNoConsumerKey()
    {
        $this->expectException(
            'Parse\ParseException',
            'Cannot link Twitter user without a consumer key.'
        );
        $this->testUserSignUp();
        $user = ParseUser::logIn('asdf', 'zxcv');
        $user->linkWithTwitter('qwer', 'asdf', null, null, 'bogus', 'bogus');
    }

    public function testLinkWithTwitterNoAuthToken()
    {
        $this->expectException(
            'Parse\ParseException',
            'Cannot link Twitter user without an auth token.'
        );
        $this->testUserSignUp();
        $user = ParseUser::logIn('asdf', 'zxcv');
        $user->linkWithTwitter('qwer', 'asdf', 'zxcv', null, null, 'bogus');
    }

    public function testLinkWithTwitterNoAuthTokenSecret()
    {
        $this->expectException(
            'Parse\ParseException',
            'Cannot link Twitter user without an auth token secret.'
        );
        $this->testUserSignUp();
        $user = ParseUser::logIn('asdf', 'zxcv');
        $user->linkWithTwitter('qwer', 'asdf', 'zxcv', null, 'bogus', null);
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

        $this->expectException('Parse\ParseException', 'Invalid session token');
        ParseUser::become('garbage_token');
    }

    public function testBecomeFromCloudCode()
    {
        $sessionToken = ParseCloud::run('createTestUser', []);

        $user = ParseUser::become($sessionToken);
        $this->assertEquals(ParseUser::getCurrentUser(), $user);
        $this->assertEquals('harry', $user->get('username'));
        $this->assertEquals($user->getSessionToken(), $sessionToken);
    }

    public function testCannotSingUpAlreadyExistingUser()
    {
        $this->testUserSignUp();
        $user = ParseUser::getCurrentUser();
        $user->setPassword('zxcv');
        $this->expectException('Parse\ParseException', 'already existing user');
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

        $this->expectException('Parse\ParseException');
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

        $this->expectException('Parse\ParseException');
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
        $this->expectException(
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

        ParseUser::logIn('a', 'password');
        $this->assertTrue($user1->isCurrent());
        $this->assertFalse($user2->isCurrent());
        $this->assertFalse($user3->isCurrent());

        ParseUser::logIn('b', 'password');
        $this->assertTrue($user2->isCurrent());
        $this->assertFalse($user1->isCurrent());
        $this->assertFalse($user3->isCurrent());

        ParseUser::logIn('c', 'password');
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
        $this->assertTrue(true);
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

    /**
     * @group test-fetch-include
     */
    public function testUserFetchWithInclude()
    {
        $child = ParseObject::create('TestObject');
        $child->set('name', 'parsephp');
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
        $userAgain->fetchWithInclude(['child']);

        $this->assertEquals($userAgain->getObjectId(), $user->getObjectId());
        $this->assertEquals(
            $userAgain->get('child')->getObjectId(),
            $child->getObjectId()
        );
        $this->assertEquals(
            $userAgain->get('child')->get('name'),
            $child->get('name')
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
        $this->expectException('Parse\ParseException', 'You must call signUp');
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
        $this->expectException('Parse\ParseException', 'Object not found.');
        $query->get($user->getObjectId(), true);
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
        $result = $query->count(true);
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
        $this->expectException('Parse\ParseException', 'empty name');
        $user->signUp();
    }

    public function testUserWithMissingPassword()
    {
        $user = new ParseUser();
        $user->setUsername('test');
        $this->expectException('Parse\ParseException', 'empty password');
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

    /**
     * @group anon-login
     */
    public function testAnonymousLogin()
    {
        $user = ParseUser::loginWithAnonymous();
        $this->assertEquals(ParseUser::getCurrentUser(), $user);
        ParseUser::logOut();
    }

    /**
     * @group user-by-id-session
     */
    public function testGetCurrentUserByIdAndSession()
    {
        $user = new ParseUser();
        $user->setUsername('plainusername');
        $user->setPassword('plainpassword');
        $user->signUp();

        $id = $user->getObjectId();
        $sessionToken = $user->getSessionToken();

        $storage = ParseClient::getStorage();
        ParseUser::_clearCurrentUserVariable();
        $storage->remove('user');

        $this->assertNull(ParseUser::getCurrentUser());

        $storage->set('user', [
            'id'            => $id,
            '_sessionToken' => $sessionToken,
            'moredata'      => 'moredata'
        ]);

        $currentUser = ParseUser::getCurrentUser();
        $this->assertNotNull($currentUser);

        $this->assertFalse($currentUser->isDataAvailable());
        $currentUser->fetch();

        $this->assertEquals('plainusername', $currentUser->getUsername());

        // check our additional userdata as well
        $this->assertEquals('moredata', $currentUser->get('moredata'));

        ParseUser::logOut();
    }

    /**
     * @group verification-email
     */
    public function testRequestVerificationEmail()
    {
        $email = 'example@example.com';
        $user = new ParseUser();
        $user->setUsername('verification_email_user');
        $user->setPassword('password');
        $user->setEmail($email);
        $user->signUp();
        ParseUser::requestVerificationEmail($email);
        $this->assertTrue(true);
    }

    /**
     * @group verification-email
     */
    public function testEmailAlreadyVerified()
    {
        $email = 'example2@example.com';
        $this->expectException('Parse\ParseException', "Email {$email} is already verified.");

        $user = new ParseUser();
        $user->setUsername('another_verification_email_user');
        $user->setPassword('password');
        $user->setEmail($email);
        $user->signUp();

        // forcibly update emailVerification status
        $user->set('emailVerified', true);
        $user->save(true);

        ParseUser::requestVerificationEmail($email);
    }

    /**
     * @group verification-email
     */
    public function testRequestVerificationEmailEmpty()
    {
        $this->expectException('Parse\ParseException', 'you must provide an email');
        ParseUser::requestVerificationEmail('');
    }

    /**
     * @group verification-email
     */
    public function testRequestVerificationEmailBad()
    {
        $this->expectException('Parse\ParseException', 'No user found with email not_a_known_email');
        ParseUser::requestVerificationEmail('not_a_known_email');
    }

    public function testRegisteringAnonymousClearsAuthData()
    {
        $user = ParseUser::loginWithAnonymous();
        $response = ParseClient::_request('GET', 'users', null, null, true);
        $this->assertNotNull($response['results'][0]['authData']['anonymous']);
        $user->setUsername('Mary');
        $user->save();
        $response = ParseClient::_request('GET', 'users', null, null, true);
        $this->assertArrayNotHasKey('authData', $response['results'][0])    ;
    }
}

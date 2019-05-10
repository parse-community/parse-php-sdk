<?php

namespace Parse\Test;

use Parse\ParseClient;
use Parse\ParseSession;
use Parse\ParseUser;

use PHPUnit\Framework\TestCase;

class ParseSessionTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        Helper::setUp();
        Helper::clearClass(ParseUser::$parseClassName);
        Helper::clearClass(ParseSession::$parseClassName);
    }

    public function tearDown() : void
    {
        Helper::tearDown();
        ParseUser::logOut();
        Helper::clearClass(ParseUser::$parseClassName);
        Helper::clearClass(ParseSession::$parseClassName);
    }

    public static function tearDownAfterClass() : void
    {
        ParseUser::_unregisterSubclass();
        ParseSession::_unregisterSubclass();
    }

    public function testRevocableSession()
    {
        ParseClient::enableRevocableSessions();
        $user = new ParseUser();
        $user->setUsername('username');
        $user->setPassword('password');
        $user->signUp();
        $session = ParseSession::getCurrentSession();
        $this->assertEquals($user->getSessionToken(), $session->getSessionToken());
        $this->assertTrue($session->isCurrentSessionRevocable());

        ParseUser::logOut();

        $this->assertFalse(ParseSession::isCurrentSessionRevocable());

        ParseUser::logIn('username', 'password');
        $session = ParseSession::getCurrentSession();
        $this->assertEquals(ParseUser::getCurrentUser()->getSessionToken(), $session->getSessionToken());
        $this->assertTrue($session->isCurrentSessionRevocable());

        $sessionToken = $session->getSessionToken();

        ParseUser::logOut();

        $this->expectException('Parse\ParseException', 'Invalid session token');
        ParseUser::become($sessionToken);
    }

    /**
     * @group upgrade-to-revocable-session
     */
    public function testUpgradeToRevocableSession()
    {
        $user = new ParseUser();
        $user->setUsername('revocable_username');
        $user->setPassword('revocable_password');
        $user->signUp();

        $session = ParseSession::getCurrentSession();
        $this->assertEquals($user->getSessionToken(), $session->getSessionToken());

        // upgrade the current session (changes our session as well)
        ParseSession::upgradeToRevocableSession();

        // verify that our session has changed, and our updated current user matches it
        $session = ParseSession::getCurrentSession();
        $user = ParseUser::getCurrentUser();
        $this->assertEquals($user->getSessionToken(), $session->getSessionToken());
        $this->assertTrue($session->isCurrentSessionRevocable());
    }

    /**
     * @group upgrade-to-revocable-session
     */
    public function testBadUpgradeToRevocableSession()
    {
        // upgrade the current session (changes our session as well)
        $this->expectException('Parse\ParseException', 'No session to upgrade.');
        ParseSession::upgradeToRevocableSession();
    }
}

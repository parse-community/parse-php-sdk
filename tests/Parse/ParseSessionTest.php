<?php

namespace Parse\Test;

use Parse\ParseClient;
use Parse\ParseSession;
use Parse\ParseUser;

class ParseSessionTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Helper::setUp();
        Helper::clearClass(ParseUser::$parseClassName);
        Helper::clearClass(ParseSession::$parseClassName);
    }

    public function tearDown()
    {
        Helper::tearDown();
        ParseUser::logOut();
        Helper::clearClass(ParseUser::$parseClassName);
        Helper::clearClass(ParseSession::$parseClassName);
    }

    public static function tearDownAfterClass()
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

        ParseUser::logIn('username', 'password');
        $session = ParseSession::getCurrentSession();
        $this->assertEquals(ParseUser::getCurrentUser()->getSessionToken(), $session->getSessionToken());
        $this->assertTrue($session->isCurrentSessionRevocable());

        $sessionToken = $session->getSessionToken();

        ParseUser::logOut();

        $this->setExpectedException('Parse\ParseException', 'invalid session token');
        ParseUser::become($sessionToken);
    }
}

<?php
namespace Parse\Test;

use Parse\ParseClient;
use Parse\ParseUser;
use Parse\ParseSession;

class ParseSessionFixationTest extends \PHPUnit_Framework_TestCase
{

    public static function setUpBeforeClass()
    {
        Helper::clearClass(ParseUser::$parseClassName);
        Helper::clearClass(ParseSession::$parseClassName);
        ParseUser::logout();
        ParseClient::_unsetStorage();

        // indicate we should not use cookies
        ini_set("session.use_cookies", 0);
        // indicate we can use something other than cookies
        ini_set("session.use_only_cookies", 0);
        // enable transparent sid support, for url based sessions
        ini_set("session.use_trans_sid", 1);
        // clear cache control for session pages
        ini_set("session.cache_limiter", "");
        session_start();
        Helper::setUp();
    }

    public function tearDown()
    {
        Helper::tearDown();
        Helper::clearClass(ParseUser::$parseClassName);
        Helper::clearClass(ParseSession::$parseClassName);
        ParseUser::logout();
    }

    public static function tearDownAfterClass()
    {
        session_destroy();
    }

    public function testCookieIdChangedForAnonymous()
    {
        ParseClient::getStorage()->set('test', 'hi');
        $noUserSessionId = session_id();
        $user = ParseUser::loginWithAnonymous();
        $anonymousSessionId = session_id();
        $this->assertNotEquals($noUserSessionId, $anonymousSessionId);
        $this->assertEquals(ParseClient::getStorage()->get('test'), 'hi');
    }

    public function testCookieIdChangedForAnonymousToRegistered()
    {
        $user = ParseUser::loginWithAnonymous();
        $anonymousSessionId = session_id();
        ParseClient::getStorage()->set('test', 'hi');
        $user->setUsername('testy');
        $user->setPassword('testy');
        $user->save();
        $user->login('testy', 'testy');
        $registeredSessionId = session_id();
        $this->assertNotEquals($anonymousSessionId, $registeredSessionId);
        $this->assertEquals(ParseClient::getStorage()->get('test'), 'hi');
    }
}

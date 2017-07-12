<?php
/**
 * Created by PhpStorm.
 * User: Bfriedman
 * Date: 1/29/17
 * Time: 10:21 AM
 */

namespace Parse\Test;

use Parse\HttpClients\ParseCurlHttpClient;
use Parse\HttpClients\ParseStreamHttpClient;
use Parse\ParseClient;
use Parse\ParseException;
use Parse\ParseInstallation;
use Parse\ParseMemoryStorage;
use Parse\ParseObject;
use Parse\ParseRole;
use Parse\ParseSessionStorage;
use Parse\ParseUser;

class ParseClientTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Helper::setUp();
    }

    public function setUp()
    {
        Helper::setServerURL();
        Helper::setHttpClient();
    }

    public function tearDown()
    {
        Helper::tearDown();

        // unset CA file
        ParseClient::setCAFile(null);
    }

    /**
     * @group client-not-initialized
     */
    public function testParseNotInitialized()
    {
        $this->setExpectedException(
            '\Exception',
            'You must call ParseClient::initialize() before making any requests.'
        );

        ParseClient::initialize(
            null,
            null,
            null
        );

        ParseClient::_request(
            '',
            ''
        );
    }

    /**
     * @group client-not-initialized
     */
    public function testAppNotNotInitialized()
    {
        $this->setExpectedException(
            '\Exception',
            'You must call ParseClient::initialize(..., $accountKey) before making any app requests. '.
            'Your account key must not be null or empty.'
        );

        ParseClient::initialize(
            null,
            null,
            null
        );

        ParseClient::_request(
            '',
            '',
            null,
            null,
            false,
            true
        );
    }

    /**
     * @group client-init
     */
    public function testInitialize()
    {

        // unregister associated sub classes
        ParseUser::_unregisterSubclass();
        ParseRole::_unregisterSubclass();
        ParseInstallation::_unregisterSubclass();

        // unset storage
        ParseClient::_unsetStorage();

        // call init
        ParseClient::initialize(
            Helper::$appId,
            Helper::$restKey,
            Helper::$masterKey,
            true,
            Helper::$accountKey
        );

        // verify these classes are now registered
        $this->assertTrue(ParseObject::hasRegisteredSubclass('_User'));
        $this->assertTrue(ParseObject::hasRegisteredSubclass('_Role'));
        $this->assertTrue(ParseObject::hasRegisteredSubclass('_Installation'));

        // verify storage is now set
        $this->assertNotNull(ParseClient::getStorage());
    }

    /**
     * @group client-storage
     */
    public function testStorage()
    {

        // unset storage
        ParseClient::_unsetStorage();

        // call init
        ParseClient::initialize(
            Helper::$appId,
            Helper::$restKey,
            Helper::$masterKey,
            true,
            Helper::$accountKey
        );

        $storage = ParseClient::getStorage();
        $this->assertTrue(
            $storage instanceof ParseMemoryStorage,
            'Not an instance of ParseMemoryStorage'
        );

        /* TODO can't get session storage test to pass properly
        // unset storage
        ParseClient::_unsetStorage();

        // indicate we should not use cookies
        ini_set("session.use_cookies", 0);
        // indicate we can use something other than cookies
        ini_set("session.use_only_cookies", 0);
        // enable transparent sid support, for url based sessions
        ini_set("session.use_trans_sid", 1);
        // clear cache control for session pages
        ini_set("session.cache_limiter", "");

        // start a session
        session_start();

        // call init
        ParseClient::initialize(
            Helper::$appId,
            Helper::$restKey,
            Helper::$masterKey,
            true,
            Helper::$accountKey
        );

        $storage = ParseClient::getStorage();
        $this->assertTrue($storage instanceof ParseSessionStorage,
            'Not an instance of ParseSessionStorage');
        */
    }

    /**
     * @group client-test
     */
    public function testSetServerURL()
    {
        // add extra slashes to test removal
        ParseClient::setServerURL('https://example.com//', '//parse//');

        // verify APIUrl
        $this->assertEquals(
            'https://example.com/parse/',
            ParseClient::getAPIUrl()
        );

        // verify mount path
        $this->assertEquals(
            'parse/',
            ParseClient::getMountPath()
        );
    }

    /**
     * @group client-test
     */
    public function testRootMountPath()
    {
        ParseClient::setServerURL('https://example.com', '/');
        $this->assertEquals(
            '',
            ParseClient::getMountPath(),
            'Mount path was not reduced to an empty sequence for root'
        );
    }

    /**
     * @group client-test
     */
    public function testBadServerURL()
    {
        $this->setExpectedException(
            '\Exception',
            'Invalid Server URL.'
        );
        ParseClient::setServerURL(null, 'parse');
    }

    /**
     * @group client-test
     */
    public function testBadMountPath()
    {
        $this->setExpectedException(
            '\Exception',
            'Invalid Mount Path.'
        );
        ParseClient::setServerURL('https://example.com', null);
    }

    /**
     * @group encoding-error
     */
    public function testEncodingError()
    {
        $this->setExpectedException(
            '\Exception',
            'Invalid type encountered.'
        );
        ParseClient::_encode(new Helper(), false);
    }

    /**
     * @group client-decoding
     */
    public function testDecodingStdClass()
    {
        $obj = new \stdClass();
        $obj->property = 'value';

        $this->assertEquals([
            'property' => 'value'
        ], ParseClient::_decode($obj));

        $emptyClass = new \stdClass();
        $this->assertEquals($emptyClass, ParseClient::_decode($emptyClass));
    }

    /**
     * @group timeouts
     */
    public function testCurlTimeout()
    {

        ParseClient::setTimeout(3000);

        // perform a standard save
        $obj = new ParseObject('TestingClass');
        $obj->set('key', 'value');
        $obj->save(true);

        $this->assertNotNull($obj->getObjectId());

        $obj->destroy();

        // clear timeout
        ParseClient::setTimeout(null);
    }

    /**
     * @group timeouts
     */
    public function testCurlConnectionTimeout()
    {
        ParseClient::setConnectionTimeout(3000);

        // perform a standard save
        $obj = new ParseObject('TestingClass');
        $obj->set('key', 'value');
        $obj->save();

        $this->assertNotNull($obj->getObjectId());

        $obj->destroy();

        // clear timeout
        ParseClient::setConnectionTimeout(null);
    }

    /**
     * @group timeouts
     */
    public function testStreamTimeout()
    {

        ParseClient::setHttpClient(new ParseStreamHttpClient());

        ParseClient::setTimeout(3000);

        // perform a standard save
        $obj = new ParseObject('TestingClass');
        $obj->set('key', 'value');
        $obj->save(true);

        $this->assertNotNull($obj->getObjectId());

        $obj->destroy();

        // clear timeout
        ParseClient::setTimeout(null);
    }

    /**
     * @group timeouts
     */
    public function testStreamConnectionTimeout()
    {

        ParseClient::setHttpClient(new ParseStreamHttpClient());

        ParseClient::setConnectionTimeout(3000);

        // perform a standard save
        $obj = new ParseObject('TestingClass');
        $obj->set('key', 'value');
        $obj->save();

        $this->assertNotNull($obj->getObjectId());

        $obj->destroy();

        // clear timeout
        ParseClient::setConnectionTimeout(null);
    }

    /**
     * @group no-curl-exceptions
     */
    public function testNoCurlExceptions()
    {
        Helper::setUpWithoutCURLExceptions();

        ParseClient::setServerURL('http://404.example.com', 'parse');
        $result = ParseClient::_request(
            'GET',
            'not-a-real-endpoint-to-reach',
            null
        );

        $this->assertFalse($result);

        // put back
        Helper::setUp();
    }

    /**
     * @group curl-exceptions
     */
    public function testCurlException()
    {
        if (function_exists('curl_init')) {
            ParseClient::setHttpClient(new ParseCurlHttpClient());

            $this->setExpectedException('\Parse\ParseException', '', 6);

            ParseClient::setServerURL('http://404.example.com', 'parse');
            ParseClient::_request(
                'GET',
                'not-a-real-endpoint-to-reach',
                null
            );
        }
    }

    /**
     * @group stream-exceptions
     */
    public function testStreamException()
    {

        ParseClient::setHttpClient(new ParseStreamHttpClient());

        $this->setExpectedException('\Parse\ParseException', '', 2);

        ParseClient::setServerURL('http://404.example.com', 'parse');
        ParseClient::_request(
            'GET',
            'not-a-real-endpoint-to-reach',
            null
        );
    }

    /**
     * @group stream-bad-request
     *
     * **NOTE**
     * file_get_contents may SOMETIMES not return a full set of headers.
     * This causes this case to fail frequently while not being a serious error.
     * If you are running test cases and are having problems with this,
     *  run it a few more times and you should be OK
     */
    public function testBadStreamRequest()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            "Bad Request"
        );

        ParseClient::setHttpClient(new ParseStreamHttpClient());

        ParseClient::setServerURL('http://example.com', '/');
        ParseClient::_request(
            'GET',
            '',
            null
        );
    }

    /**
     * @group client-bad-request
     */
    public function testCurlBadRequest()
    {
        if (function_exists('curl_init')) {
            $this->setExpectedException(
                '\Parse\ParseException',
                "Bad Request"
            );

            ParseClient::setHttpClient(new ParseCurlHttpClient());

            ParseClient::setServerURL('http://example.com', '/');
            ParseClient::_request(
                'GET',
                '',
                null
            );
        }
    }

    /**
     * @group default-http-client
     */
    public function testGetDefaultHttpClient()
    {
        // clear existing client
        ParseClient::clearHttpClient();

        // get default client
        $default = ParseClient::getHttpClient();

        if (function_exists('curl_init')) {
            // should be a curl client
            $this->assertTrue($default instanceof ParseCurlHttpClient);
        } else {
            // should be a stream client
            $this->assertTrue($default instanceof ParseStreamHttpClient);
        }
    }

    /**
     * @group ca-file
     */
    public function testCurlCAFile()
    {
        if (function_exists('curl_init')) {
            // set a curl client
            ParseClient::setHttpClient(new ParseCurlHttpClient());

            // not a real ca file, just testing setting
            ParseClient::setCAFile("not-real-ca-file");

            $this->setExpectedException(
                '\Parse\ParseException',
                "Bad Request"
            );

            ParseClient::setServerURL('http://example.com', '/');
            ParseClient::_request(
                'GET',
                '',
                null
            );
        }
    }

    /**
     * @group ca-file
     */
    public function testStreamCAFile()
    {
        // set a stream client
        ParseClient::setHttpClient(new ParseStreamHttpClient());

        // not a real ca file, just testing setting
        ParseClient::setCAFile("not-real-ca-file");

        $this->setExpectedException(
            '\Parse\ParseException',
            "Bad Request"
        );

        ParseClient::setServerURL('http://example.com', '/');
        ParseClient::_request(
            'GET',
            '',
            null
        );
    }

    /**
     * @group api-not-set
     */
    public function testURLNotSet()
    {
        $this->setExpectedException(
            '\Exception',
            'Missing a valid server url. '.
            'You must call ParseClient::setServerURL(\'https://your.parse-server.com\', \'/parse\') '.
            ' before making any requests.'
        );

        ParseClient::_clearServerURL();
        (new ParseObject('TestingClass'))->save();
    }

    /**
     * @group api-not-set
     */
    public function testMountPathNotSet()
    {
        $this->setExpectedException(
            '\Exception',
            'Missing a valid mount path. '.
            'You must call ParseClient::setServerURL(\'https://your.parse-server.com\', \'/parse\') '.
            ' before making any requests.'
        );

        ParseClient::_clearMountPath();
        (new ParseObject('TestingClass'))->save();
    }

    /**
     * @group bad-api-response
     */
    public function testBadApiResponse()
    {
        $this->setExpectedException(
            '\Parse\ParseException',
            'Bad Request. Could not decode Response: (4) Syntax error'
        );

        $httpClient = ParseClient::getHttpClient();

        // create a mock of the current http client
        $stubClient = $this->getMockBuilder(get_class($httpClient))
            ->getMock();

        // stub the response type to return
        // something we will try to work with
        $stubClient
            ->method('getResponseContentType')
            ->willReturn('application/octet-stream');

        $stubClient
            ->method('send')
            ->willReturn('This is not valid json!');

        // replace the client with our stub
        ParseClient::setHttpClient($stubClient);

        // attempt to save, which should not fire our given code
        $obj = new ParseObject('TestingClass');
        $obj->save();
    }
}

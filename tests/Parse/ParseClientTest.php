<?php
/**
 * Class ParseClientTest | Parse/Test/ParseClientTest.php
 */

namespace Parse\Test;

use Parse\HttpClients\ParseCurlHttpClient;
use Parse\HttpClients\ParseStreamHttpClient;
use Parse\ParseClient;
use Parse\ParseInstallation;
use Parse\ParseMemoryStorage;
use Parse\ParseObject;
use Parse\ParseRole;
use Parse\ParseUser;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

defined('CURLOPT_PINNEDPUBLICKEY') || define('CURLOPT_PINNEDPUBLICKEY', 10230);

class ParseClientTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        Helper::setUp();
    }

    public function setup() : void
    {
        Helper::setServerURL();
        Helper::setHttpClient();
    }

    public function tearDown() : void
    {
        Helper::tearDown();

        // unset CA file
        ParseClient::setCAFile(null);

        // unset http options
        ParseClient::setHttpOptions(null);
    }

    #[Group('client-not-initialized')]
    public function testParseNotInitialized()
    {
        $this->expectException(
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

    #[Group('client-init')]
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
        );

        // verify these classes are now registered
        $this->assertTrue(ParseObject::hasRegisteredSubclass('_User'));
        $this->assertTrue(ParseObject::hasRegisteredSubclass('_Role'));
        $this->assertTrue(ParseObject::hasRegisteredSubclass('_Installation'));

        // verify storage is now set
        $this->assertNotNull(ParseClient::getStorage());
    }

    #[Group('client-storage')]
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
        );

        $storage = ParseClient::getStorage();
        $this->assertTrue($storage instanceof ParseSessionStorage,
            'Not an instance of ParseSessionStorage');
        */
    }

    #[Group('client-test')]
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

    #[Group('client-test')]
    public function testRootMountPath()
    {
        ParseClient::setServerURL('https://example.com', '/');
        $this->assertEquals(
            '',
            ParseClient::getMountPath(),
            'Mount path was not reduced to an empty sequence for root'
        );
    }

    #[Group('client-test')]
    public function testBadServerURL()
    {
        $this->expectException(
            '\Exception',
            'Invalid Server URL.'
        );
        ParseClient::setServerURL(null, 'parse');
    }

    #[Group('client-test')]
    public function testBadMountPath()
    {
        $this->expectException(
            '\Exception',
            'Invalid Mount Path.'
        );
        ParseClient::setServerURL('https://example.com', null);
    }

    #[Group('encoding-error')]
    public function testEncodingError()
    {
        $this->expectException(
            '\Exception',
            'Invalid type encountered.'
        );
        ParseClient::_encode(new Helper(), false);
    }

    #[Group('client-decoding')]
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

    #[Group('timeouts')]
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

    #[Group('timeouts')]
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

    #[Group('timeouts')]
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

    #[Group('timeouts')]
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

    #[Group('no-curl-exceptions')]
    public function testNoCurlExceptions()
    {
        global $USE_CLIENT_STREAM;
        if (isset($USE_CLIENT_STREAM)) {
            $this->markTestSkipped('Skipping curl exception test');
        }
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

    #[Group('curl-exceptions')]
    public function testCurlException()
    {
        if (function_exists('curl_init')) {
            ParseClient::setHttpClient(new ParseCurlHttpClient());

            $this->expectException('\Parse\ParseException', '', 6);

            ParseClient::setServerURL('http://404.example.com', 'parse');
            ParseClient::_request(
                'GET',
                'not-a-real-endpoint-to-reach',
                null
            );
        }
    }

    #[Group('stream-exceptions')]
    public function testStreamException()
    {

        ParseClient::setHttpClient(new ParseStreamHttpClient());

        $this->expectException('\Parse\ParseException', '', 2);

        ParseClient::setServerURL('http://404.example.com', 'parse');
        ParseClient::_request(
            'GET',
            'not-a-real-endpoint-to-reach',
            null
        );
    }

    /**     *
     * **NOTE**
     * file_get_contents may SOMETIMES not return a full set of headers.
     * This causes this case to fail frequently while not being a serious error.
     * If you are running test cases and are having problems with this,
     *  run it a few more times and you should be OK
     */
    #[Group('stream-bad-request')]
    public function testBadStreamRequest()
    {
        $this->expectException(
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

    #[Group('client-bad-request')]
    public function testCurlBadRequest()
    {
        if (function_exists('curl_init')) {
            $this->expectException(
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

    #[Group('default-http-client')]
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

    #[Group('ca-file')]
    public function testCurlCAFile()
    {
        if (function_exists('curl_init')) {
            // set a curl client
            ParseClient::setHttpClient(new ParseCurlHttpClient());

            // not a real ca file, just testing setting
            ParseClient::setCAFile("not-real-ca-file");

            $this->expectException(
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

    #[Group('ca-file')]
    public function testStreamCAFile()
    {
        // set a stream client
        ParseClient::setHttpClient(new ParseStreamHttpClient());

        // not a real ca file, just testing setting
        ParseClient::setCAFile("not-real-ca-file");

        $this->expectException(
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

    #[Group('api-not-set')]
    public function testURLNotSet()
    {
        $this->expectException(
            '\Exception',
            'Missing a valid server url. '.
            'You must call ParseClient::setServerURL(\'https://your.parse-server.com\', \'/parse\') '.
            ' before making any requests.'
        );

        ParseClient::_clearServerURL();
        (new ParseObject('TestingClass'))->save();
    }

    #[Group('api-not-set')]
    public function testMountPathNotSet()
    {
        $this->expectException(
            '\Exception',
            'Missing a valid mount path. '.
            'You must call ParseClient::setServerURL(\'https://your.parse-server.com\', \'/parse\') '.
            ' before making any requests.'
        );

        ParseClient::_clearMountPath();
        (new ParseObject('TestingClass'))->save();
    }

    #[Group('bad-api-response')]
    public function testBadApiResponse()
    {
        $this->expectException(
            '\Parse\ParseException',
            'Bad Request. Could not decode Response: (4) Syntax error'
        );

        $httpClient = ParseClient::getHttpClient();

        // create a mock of the current http client
        $stubClient = $this->createStub(get_class($httpClient));

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

    #[Group('check-server')]
    public function testCheckServer()
    {
        $health = ParseClient::getServerHealth();

        $this->assertNotNull($health);
        $this->assertEquals($health['status'], 200);
        $this->assertEquals($health['response']['status'], 'ok');
    }

    /**
     * Structured response present in modified/later versions of parse-server
     *
     */
    #[Group('check-server')]
    public function testStructuredHealthResponse()
    {
        $httpClient = ParseClient::getHttpClient();

        // create a mock of the current http client
        $stubClient = $this->createStub(get_class($httpClient));

        // stub the response type to return
        // something we will try to work with
        $stubClient
            ->method('getResponseContentType')
            ->willReturn('application/octet-stream');

        $stubClient
            ->method('getResponseStatusCode')
            ->willReturn(200);

        $stubClient
            ->method('send')
            ->willReturn('{"status":"ok"}');

        // replace the client with our stub
        ParseClient::setHttpClient($stubClient);

        $health = ParseClient::getServerHealth();

        $this->assertNotNull($health);
        $this->assertEquals($health['status'], 200);
        $this->assertEquals($health['response']['status'], 'ok');
    }

    /**
     * Plain response present in earlier versions of parse-server (from 2.2.25 on)
     */
    #[Group('check-server')]
    public function testPlainHealthResponse()
    {
        $httpClient = ParseClient::getHttpClient();

        // create a mock of the current http client
        $stubClient = $this->createStub(get_class($httpClient));

        // stub the response type to return
        // something we will try to work with
        $stubClient
            ->method('getResponseContentType')
            ->willReturn('text/plain');

        $stubClient
            ->method('getResponseStatusCode')
            ->willReturn(200);

        $stubClient
            ->method('send')
            ->willReturn('OK');

        // replace the client with our stub
        ParseClient::setHttpClient($stubClient);

        $health = ParseClient::getServerHealth();

        $this->assertNotNull($health);
        $this->assertEquals($health['status'], 200);
        $this->assertEquals($health['response']['status'], 'ok');
    }

    #[Group('check-server')]
    public function testCheckBadServer()
    {
        ParseClient::setServerURL(Helper::getHttpServerURL(), 'not-a-real-endpoint');
        $health = ParseClient::getServerHealth();
        $this->assertNotNull($health);
        $this->assertFalse(isset($health['error']));
        $this->assertFalse(isset($health['error_message']));
        $this->assertEquals($health['status'], 404);

        ParseClient::setServerURL('http://___uh___oh___.com', 'parse');
        $health = ParseClient::getServerHealth();

        global $USE_CLIENT_STREAM;
        if (!isset($USE_CLIENT_STREAM)) {
            $this->assertTrue(isset($health['error']));
            $this->assertTrue(isset($health['error_message']));
        }
    }

    #[Group('test-http-options')]
    public function testCurlHttpOptions()
    {
        if (function_exists('curl_init')) {
            ParseClient::setHttpClient(new ParseCurlHttpClient());
            ParseClient::setServerURL(Helper::getHttpsServerURL(), 'parse');
            ParseClient::setHttpOptions([
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_PINNEDPUBLICKEY => 'sha256//Oz+R70/uIv0irdBWc7RNPyCGeZNbN+CBiPLjJxXWigg=',
                CURLOPT_SSLCERT => dirname(__DIR__).'/keys/client.crt',
                CURLOPT_SSLKEY => dirname(__DIR__).'/keys/client.key',
            ]);
            $health = ParseClient::getServerHealth();

            $this->assertNotNull($health);
            $this->assertEquals($health['status'], 200);
            $this->assertEquals($health['response']['status'], 'ok');
            Helper::setServerURL();
        }
    }

    #[Group('test-http-options')]
    public function testStreamHttpOptions()
    {
        ParseClient::setHttpClient(new ParseStreamHttpClient());
        ParseClient::setServerURL(Helper::getHttpsServerURL(), 'parse');
        ParseClient::setHttpOptions([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'local_cert' => dirname(__DIR__).'/keys/client.crt',
                'local_pk' => dirname(__DIR__).'/keys/client.key',
                'peer_fingerprint' => '5036363125B57017255B3BB3241666827BD6A3A8',
            ]
        ]);
        $health = ParseClient::getServerHealth();

        $this->assertNotNull($health);
        $this->assertEquals($health['status'], 200);
        $this->assertEquals($health['response']['status'], 'ok');
        Helper::setServerURL();
    }
}

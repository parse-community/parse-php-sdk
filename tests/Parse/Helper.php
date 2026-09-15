<?php

namespace Parse\Test;

use Parse\HttpClients\ParseCurlHttpClient;
use Parse\HttpClients\ParseStreamHttpClient;
use Parse\ParseClient;
use Parse\ParseObject;
use Parse\ParseQuery;

class Helper
{
    /**
     * Application Id
     * @var string
     */
    public static $appId      = 'app-id-here';

    /**
     * Rest API Key
     * @var string
     */
    public static $restKey    = 'rest-api-key-here';

    /**
     * Master Key
     * @var string
     */
    public static $masterKey  = 'master-key-here';

    public static function setUp()
    {
        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', 1);
        date_default_timezone_set('UTC');

        ParseClient::initialize(
            self::$appId,
            self::$restKey,
            self::$masterKey,
            true,
        );
        self::setServerURL();
        self::setHttpClient();
    }

    public static function setHttpClient()
    {
        //
        // Set a curl http client to run primary tests under
        // may be:
        //
        // ParseCurlHttpClient
        // ParseStreamHttpClient
        //

        global $USE_CLIENT_STREAM;

        if (isset($USE_CLIENT_STREAM)) {
            // stream client
            ParseClient::setHttpClient(new ParseStreamHttpClient());
        } else {
            // default client set
            if (function_exists('curl_init')) {
                // cURL client
                ParseClient::setHttpClient(new ParseCurlHttpClient());
            } else {
                // stream client
                ParseClient::setHttpClient(new ParseStreamHttpClient());
            }
        }
    }

    public static function setServerURL()
    {
        ParseClient::setServerURL(self::getHttpServerURL(), 'parse');
    }

    /**
     * Base url of the plain http test server.
     *
     * The port may be overridden with PARSE_TEST_HTTP_PORT to avoid
     * clashing with another Parse server running locally.
     *
     * @return string
     */
    public static function getHttpServerURL()
    {
        return 'http://localhost:'.self::getPort('PARSE_TEST_HTTP_PORT', 1337);
    }

    /**
     * Base url of the TLS enabled test server.
     *
     * The port may be overridden with PARSE_TEST_HTTPS_PORT.
     *
     * @return string
     */
    public static function getHttpsServerURL()
    {
        return 'https://localhost:'.self::getPort('PARSE_TEST_HTTPS_PORT', 1338);
    }

    /**
     * Reads a port from the environment, applying the same validation as
     * tests/server.js so both ends always agree on the port in use.
     *
     * @param string $variable  Name of the environment variable to read
     * @param int    $default   Port to use when the variable is not set
     *
     * @throws \InvalidArgumentException
     *
     * @return int
     */
    private static function getPort($variable, $default)
    {
        $port = getenv($variable);

        if ($port === false || $port === '') {
            return $default;
        }

        if (!ctype_digit($port) || (int) $port < 1 || (int) $port > 65535) {
            throw new \InvalidArgumentException(
                $variable.' must be an integer between 1 and 65535, got "'.$port.'"'
            );
        }

        return (int) $port;
    }

    public static function tearDown()
    {
    }

    public static function clearClass($class)
    {
        $query = new ParseQuery($class);
        $query->each(
            function (ParseObject $obj) {
                $obj->destroy(true);
            },
            true
        );
    }

    public static function setUpWithoutCURLExceptions()
    {
        ParseClient::initialize(
            self::$appId,
            self::$restKey,
            self::$masterKey,
            false,
        );
    }

    public static function print($text)
    {
        fwrite(STDOUT, $text . "\n");
    }

    public static function printArray($array)
    {
        print_r($array);
        ob_end_flush();
    }
}

<?php

namespace Parse;

use Exception;
use Parse\HttpClients\ParseCurlHttpClient;
use Parse\HttpClients\ParseHttpable;
use Parse\HttpClients\ParseStreamHttpClient;
use Parse\Internal\Encodable;

/**
 * ParseClient - Main class for Parse initialization and communication.
 *
 * @author Fosco Marotto <fjm@fb.com>
 */
final class ParseClient
{
    /**
     * The remote Parse Server to communicate with
     *
     * @var string
     */
    private static $serverURL = 'https://api.parse.com/';

    /**
     * The mount path for the current parse server
     *
     * @var string
     */
    private static $mountPath = "1/";

    /**
     * The application id.
     *
     * @var string
     */
    private static $applicationId;

    /**
     * The REST API Key.
     *
     * @var string|null
     */
    private static $restKey;

    /**
     * The Master Key.
     *
     * @var string
     */
    private static $masterKey;

    /**
     * Enable/Disable curl exceptions.
     *
     * @var bool
     */
    private static $enableCurlExceptions;

    /**
     * The account key.
     *
     * @var string
     */
    private static $accountKey;

    /**
     * The object for managing persistence.
     *
     * @var ParseStorageInterface
     */
    private static $storage;

    /**
     * Are revocable sessions enabled?
     *
     * @var bool
     */
    private static $forceRevocableSession = false;

    /**
     * Number of seconds to wait while trying to connect. Use 0 to wait indefinitely.
     *
     * @var int
     */
    private static $connectionTimeout;

    /**
     * Maximum number of seconds of request/response operation.
     *
     * @var int
     */
    private static $timeout;

    /**
     * Http client for requests
     *
     * @var ParseHttpable
     */
    private static $httpClient;

    /**
     * CA file holding one or more certificates to verify a peer
     *
     * @var string
     */
    private static $caFile;

    /**
     * Constant for version string to include with requests.
     *
     * @var string
     */
    const VERSION_STRING = 'php1.2.8';

    /**
     * Parse\Client::initialize, must be called before using Parse features.
     *
     * @param string $app_id               Parse Application ID
     * @param string $rest_key             Parse REST API Key
     * @param string $master_key           Parse Master Key
     * @param bool   $enableCurlExceptions Enable or disable Parse curl exceptions
     * @param string $account_key          An account key from Parse.com can enable creating apps via API.
     *
     * @throws Exception
     */
    public static function initialize($app_id, $rest_key, $master_key, $enableCurlExceptions = true, $account_key = null)
    {
        if (!ParseObject::hasRegisteredSubclass('_User')) {
            ParseUser::registerSubclass();
        }

        if (!ParseObject::hasRegisteredSubclass('_Role')) {
            ParseRole::registerSubclass();
        }

        if (!ParseObject::hasRegisteredSubclass('_Installation')) {
            ParseInstallation::registerSubclass();
        }

        if(!ParseObject::hasRegisteredSubclass('_PushStatus')) {
            ParsePushStatus::registerSubclass();
        }

        ParseSession::registerSubclass();
        self::$applicationId = $app_id;
        self::$restKey = $rest_key;
        self::$masterKey = $master_key;
        self::$enableCurlExceptions = $enableCurlExceptions;
        self::$accountKey = $account_key;
        if (!static::$storage) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                self::setStorage(new ParseSessionStorage());
            } else {
                self::setStorage(new ParseMemoryStorage());
            }
        }
    }

    /**
     * ParseClient::setServerURL, to change the Parse Server address & mount path for this app
     * @param string $serverURL     The remote server url
     * @param string $mountPath     The mount path for this server
     *
     * @throws \Exception
     *
     */
    public static function setServerURL($serverURL, $mountPath) {
        if (!$serverURL) {
            throw new Exception('Invalid Server URL.');
        }
        if( !$mountPath) {
            throw new Exception('Invalid Mount Path.');
        }

        self::$serverURL = rtrim($serverURL,'/');
        self::$mountPath = trim($mountPath,'/') . '/';

        // check if mount path is root
        if(self::$mountPath == "/") {
            // root path should have no mount path
            self::$mountPath = "";
        }
    }

    /**
     * Sets the Http client to use for requests
     *
     * @param ParseHttpable $httpClient Http client to use
     */
    public static function setHttpClient(ParseHttpable $httpClient)
    {
        self::$httpClient = $httpClient;

    }

    /**
     * Gets the current Http client, or creates one to suite the need
     *
     * @return ParseHttpable
     */
    public static function getHttpClient()
    {
        if (static::$httpClient) {
            // use existing client
            return static::$httpClient;

        } else {
            // default to cURL/stream
            return function_exists('curl_init') ? new ParseCurlHttpClient() : new ParseStreamHttpClient();

        }

    }

    /**
     * Clears the currently set http client
     */
    public static function clearHttpClient()
    {
        self::$httpClient = null;

    }

    /**
     * Sets a CA file to validate peers of our requests with
     *
     * @param string $caFile    CA file to set
     */
    public static function setCAFile($caFile)
    {
        self::$caFile = $caFile;

    }

    /**
     * ParseClient::_encode, internal method for encoding object values.
     *
     * @param mixed $value             Value to encode
     * @param bool  $allowParseObjects Allow nested objects
     *
     * @throws \Exception
     *
     * @return mixed Encoded results.
     */
    public static function _encode($value, $allowParseObjects)
    {
        if ($value instanceof \DateTime || $value instanceof \DateTimeImmutable) {
            return [
                '__type' => 'Date', 'iso' => self::getProperDateFormat($value),
            ];
        }

        if ($value instanceof \stdClass) {
            return $value;
        }

        if ($value instanceof ParseObject) {
            if (!$allowParseObjects) {
                throw new Exception('ParseObjects not allowed here.');
            }

            return $value->_toPointer();
        }

        if ($value instanceof Encodable) {
            return $value->_encode();
        }

        if (is_array($value)) {
            return self::_encodeArray($value, $allowParseObjects);
        }

        if (!is_scalar($value) && $value !== null) {
            throw new Exception('Invalid type encountered.');
        }

        return $value;
    }

    /**
     * ParseClient::_decode, internal method for decoding server responses.
     *
     * @param mixed $data The value to decode
     *
     * @return mixed
     */
    public static function _decode($data)
    {
        // The json decoded response from Parse will make JSONObjects into stdClass
        //     objects.    We'll change it to an associative array here.
        if ($data instanceof \stdClass) {
            $tmp = (array) $data;
            if (!empty($tmp)) {
                return self::_decode(get_object_vars($data));
            }
        }

        if (!isset($data) && !is_array($data)) {
            return;
        }

        if (is_array($data)) {
            $typeString = (isset($data['__type']) ? $data['__type'] : null);

            if ($typeString === 'Date') {
                return new \DateTime($data['iso']);
            }

            if ($typeString === 'Bytes') {
                return base64_decode($data['base64']);
            }

            if ($typeString === 'Pointer') {
                return ParseObject::create($data['className'], $data['objectId']);
            }

            if ($typeString === 'File') {
                return ParseFile::_createFromServer($data['name'], $data['url']);
            }

            if ($typeString === 'GeoPoint') {
                return new ParseGeoPoint($data['latitude'], $data['longitude']);
            }

            if ($typeString === 'Object') {
                $output = ParseObject::create($data['className']);
                $output->_mergeAfterFetch($data);

                return $output;
            }

            if ($typeString === 'Relation') {
                return $data;
            }

            $newDict = [];
            foreach ($data as $key => $value) {
                $newDict[$key] = static::_decode($value);
            }

            return $newDict;
        }

        return $data;
    }

    /**
     * ParseClient::_encodeArray, internal method for encoding arrays.
     *
     * @param array $value             Array to encode.
     * @param bool  $allowParseObjects Allow nested objects.
     *
     * @return array Encoded results.
     */
    public static function _encodeArray($value, $allowParseObjects)
    {
        $output = [];
        foreach ($value as $key => $item) {
            $output[$key] = self::_encode($item, $allowParseObjects);
        }

        return $output;
    }

    /**
     * Parse\Client::_request, internal method for communicating with Parse.
     *
     * @param string $method        HTTP Method for this request.
     * @param string $relativeUrl   REST API Path.
     * @param null   $sessionToken  Session Token.
     * @param null   $data          Data to provide with the request.
     * @param bool   $useMasterKey  Whether to use the Master Key.
     * @param bool   $appRequest    App request to create or modify a application
     * @param string $contentType   The content type for this request, default is application/json
     * @param bool   $returnHeaders Allow to return response headers
     *
     * @throws \Exception
     *
     * @return mixed Result from Parse API Call.
     */
    public static function _request(
        $method,
        $relativeUrl,
        $sessionToken = null,
        $data = null,
        $useMasterKey = false,
        $appRequest = false,
        $contentType = 'application/json',
        $returnHeaders = false
    ) {
        if ($data === '[]') {
            $data = '{}';
        }

        // get our http client
        $httpClient = self::getHttpClient();

        // setup
        $httpClient->setup();

        if(isset(self::$caFile)) {
            // set CA file
            $httpClient->setCAFile(self::$caFile);

        }

        if ($appRequest) {
            // ** 'app' requests are not available in open source parse-server
            self::assertAppInitialized();

            $httpClient->addRequestHeader('X-Parse-Account-Key', self::$accountKey);

        } else {
            self::assertParseInitialized();

            // add appId & client version
            $httpClient->addRequestHeader('X-Parse-Application-Id', self::$applicationId);
            $httpClient->addRequestHeader('X-Parse-Client-Version', self::VERSION_STRING);


            if ($sessionToken) {
                // add our current session token
                $httpClient->addRequestHeader('X-Parse-Session-Token', $sessionToken);

            }

            if ($useMasterKey) {
                // pass master key
                $httpClient->addRequestHeader('X-Parse-Master-Key', self::$masterKey);

            } else if(isset(self::$restKey)) {
                // pass REST key
                $httpClient->addRequestHeader('X-Parse-REST-API-Key', self::$restKey);

            }

            if (self::$forceRevocableSession) {
                // indicate we are using revocable sessions
                $httpClient->addRequestHeader('X-Parse-Revocable-Session', '1');

            }

        }

        /*
         * Set an empty Expect header to stop the 100-continue behavior for post
         *     data greater than 1024 bytes.
         *     http://pilif.github.io/2007/02/the-return-of-except-100-continue/
         */
        $httpClient->addRequestHeader('Expect', '');

        // create request url
        $url = self::$serverURL . '/' . self::$mountPath.ltrim($relativeUrl, '/');

        if($method === 'POST' || $method === 'PUT') {
            // add content type to the request
            $httpClient->addRequestHeader('Content-type', $contentType);

        }

        if (!is_null(self::$connectionTimeout)) {
            // set connection timeout
            $httpClient->setConnectionTimeout(self::$connectionTimeout);

        }

        if (!is_null(self::$timeout)) {
            // set request/response timeout
            $httpClient->setTimeout(self::$timeout);

        }

        // send our request
        $response = $httpClient->send($url, $method, $data);

        // check content type of our response
        $contentType = $httpClient->getResponseContentType();

        if (strpos($contentType, 'text/html') !== false) {
            throw new ParseException('Bad Request', -1);

        }

        if($httpClient->getErrorCode()) {
            if(self::$enableCurlExceptions) {
                throw new ParseException($httpClient->getErrorMessage(), $httpClient->getErrorCode());

            } else {
                return false;

            }
        }

        $decoded = json_decode($response, true);

        if(!isset($decoded) && $response !== '') {
            throw new ParseException(
                'Bad Request. Could not decode Response: '.
                '('.json_last_error().') '.json_last_error_msg(),
                -1
            );

        }

        if (isset($decoded['error'])) {
            // check to convert error to a string, if an array
            // used to handle an Array 'error' from back4app.com
            $errorMessage = is_array($decoded['error']) ? json_encode($decoded['error']) : $decoded['error'];
            throw new ParseException(
                $errorMessage,
                isset($decoded['code']) ? $decoded['code'] : 0
            );
        }

        if ($returnHeaders) {
            $decoded['_headers'] = $httpClient->getResponseHeaders();

        }

        return $decoded;

    }

    /**
     * ParseClient::setStorage, will update the storage object used for
     * persistence.
     *
     * @param ParseStorageInterface $storageObject
     */
    public static function setStorage(ParseStorageInterface $storageObject)
    {
        self::$storage = $storageObject;
    }

    /**
     * ParseClient::getStorage, will return the storage object used for
     * persistence.
     *
     * @return ParseStorageInterface
     */
    public static function getStorage()
    {
        return self::$storage;
    }

    /**
     * ParseClient::_unsetStorage, will null the storage object.
     *
     * Without some ability to clear the storage objects, all test cases would
     *     use the first assigned storage object.
     */
    public static function _unsetStorage()
    {
        self::$storage = null;
    }

    private static function assertParseInitialized()
    {
        if (self::$applicationId === null) {
            throw new Exception(
                'You must call Parse::initialize() before making any requests.'
            );
        }
    }

    /**
     * @throws Exception
     */
    private static function assertAppInitialized()
    {
        if (self::$accountKey === null || empty(self::$accountKey)) {
            throw new Exception(
                'You must call Parse::initialize(..., $accountKey) before making any app requests. '.
                'Your account key must not be null or empty.'
            );
        }
    }

    /**
     * Get remote Parse API url.
     *
     * @return string
     */
    public static function getAPIUrl()
    {
        return self::$serverURL.'/'.self::$mountPath;
    }

    /**
     * Get remote Parse API mount path
     *
     * @return string
     */
    public static function getMountPath()
    {
        return self::$mountPath;
    }

    /**
     * Get a date value in the format stored on Parse.
     *
     * All the SDKs do some slightly different date handling.
     * PHP provides 6 digits for the microseconds (u) so we have to chop 3 off.
     *
     * @param \DateTime $value DateTime value to format.
     *
     * @return string
     */
    public static function getProperDateFormat($value)
    {
        $dateFormatString = 'Y-m-d\TH:i:s.u';
        $date = date_format($value, $dateFormatString);
        $date = substr($date, 0, -3).'Z';

        return $date;
    }

    /**
     * Get a date value in the format to use in Local Push Scheduling on Parse.
     *
     * All the SDKs do some slightly different date handling.
     * Format from Parse doc: an ISO 8601 date without a time zone, i.e. 2014-10-16T12:00:00 .
     *
     * @param \DateTime $value DateTime value to format.
     * @param bool      $local Whether to return the local push time
     *
     * @return string
     */
    public static function getPushDateFormat($value, $local = false)
    {
        $dateFormatString = 'Y-m-d\TH:i:s';
        if (!$local) {
            $dateFormatString .= '\Z';
        }
        $date = date_format($value, $dateFormatString);

        return $date;
    }

    /**
     * Allows an existing application to start using revocable sessions, without forcing
     * all requests for the app to use them.    After calling this method, login & signup requests
     * will be returned a unique and revocable session token.
     */
    public static function enableRevocableSessions()
    {
        self::$forceRevocableSession = true;
    }

    /**
     * Sets number of seconds to wait while trying to connect. Use 0 to wait indefinitely, null to default behaviour.
     *
     * @param int|null $connectionTimeout
     */
    public static function setConnectionTimeout($connectionTimeout)
    {
        self::$connectionTimeout = $connectionTimeout;
    }

    /**
     * Sets maximum number of seconds of request/response operation.
     * Use 0 to wait indefinitely, null to default behaviour.
     *
     * @param int|null $timeout
     */
    public static function setTimeout($timeout)
    {
        self::$timeout = $timeout;
    }
}

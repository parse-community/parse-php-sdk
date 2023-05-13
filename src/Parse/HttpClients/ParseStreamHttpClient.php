<?php
/**
 * Class ParseStreamHttpClient | Parse/HttpClients/ParseStreamHttpClient.php
 */

namespace Parse\HttpClients;

use Parse\ParseException;

/**
 * Class ParseStreamHttpClient - Stream http client
 *
 * @author Ben Friedman <friedman.benjamin@gmail.com>
 * @package Parse\HttpClients
 */
class ParseStreamHttpClient implements ParseHttpable
{
    /**
     * Stream handle
     *
     * @var ParseStream
     */
    private $parseStream;

    /**
     * Request Headers
     *
     * @var array
     */
    private $headers = array();

    /**
     * Response headers
     *
     * @var array
     */
    private $responseHeaders = array();

    /**
     * Response code
     *
     * @var int
     */
    private $responseCode = 0;

    /**
     * Content type of our response
     *
     * @var string|null
     */
    private $responseContentType;

    /**
     * Stream error code
     *
     * @var int
     */
    private $streamErrorCode;

    /**
     * Stream error message
     *
     * @var string
     */
    private $streamErrorMessage;

    /**
     * Options to pass to our stream
     *
     * @var array
     */
    private $options = array();

    /**
     * Optional CA file to verify our peers with
     *
     * @var string
     */
    private $caFile;

    /**
     * Options to pass to the stream context.
     *
     * @var array
     */
    private $httpOptions;

    /**
     * Optional timeout for this request
     *
     * @var int
     */
    private $timeout;

    /**
     * ParseStreamHttpClient constructor.
     */
    public function __construct()
    {
        if (!isset($this->parseStream)) {
            $this->parseStream = new ParseStream();
        }
    }



    /**
     * Adds a header to this request
     *
     * @param string $key       Header name
     * @param string $value     Header value
     */
    public function addRequestHeader($key, $value)
    {
        $this->headers[$key]    = $value;
    }

    /**
     * Gets headers in the response
     *
     * @return array
     */
    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }

    /**
     * Returns the status code of the response
     *
     * @return int
     */
    public function getResponseStatusCode()
    {
        return $this->responseCode;
    }

    /**
     * Returns the content type of the response
     *
     * @return null|string
     */
    public function getResponseContentType()
    {
        return $this->responseContentType;
    }

    /**
     * Builds and returns the coalesced request headers
     *
     * @return array
     */
    private function buildRequestHeaders()
    {
        // coalesce our header key/value pairs
        $headers = [];
        foreach ($this->headers as $key => $value) {
            if ($key == 'Expect' && $value == '') {
                // drop this pair
                continue;
            }

            // add this header key/value pair
            $headers[] = $key.': '.$value;
        }

        return implode("\r\n", $headers);
    }

    /**
     * Sets up ssl related options for the stream context
     */
    public function setup() : void
    {
        // setup ssl options
        $this->options['ssl'] = array(
            'verify_peer'       => true,
            'verify_peer_name'  => true,
            'allow_self_signed' => true // All root certificates are self-signed
        );
    }

    /**
     * Sends an HTTP request
     *
     * @param string $url       Url to send this request to
     * @param string $method    Method to send this request via
     * @param array $data       Data to send in this request
     * @return string
     * @throws ParseException
     */
    public function send($url, $method = 'GET', $data = array())
    {

        // verify this url
        if (preg_match('/\s/', trim($url))) {
            throw new ParseException('Url may not contain spaces for stream client: '.$url);
        }

        if (isset($this->caFile)) {
            // set CA file as well
            $this->options['ssl']['cafile'] = $this->caFile;
        }

        if (isset($this->httpOptions)) {
            foreach ($this->httpOptions as $key => $value) {
                $this->options[$key] = $value;
            }
        }

        // add additional options for this request
        $this->options['http'] = array(
            'method'        => $method,
            'ignore_errors' => true
        );

        if (isset($this->timeout)) {
            $this->options['http']['timeout']   = $this->timeout;
        }

        if (isset($data) && $data != "{}") {
            if ($method == "GET") {
                // handle GET
                $url.='?'.http_build_query($data, '', '&');
                $this->addRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            } elseif ($method == "POST") {
                // handle POST
                $this->options['http']['content'] = $data;
            } elseif ($method == "PUT") {
                // handle PUT
                $this->options['http']['content'] = $data;
            }
        }

        // set headers
        if (!defined('HHVM_VERSION')) {
            // default
            $this->options['http']['header'] = $this->buildRequestHeaders();
        } else {
            /**
             * HHVM bug bypass
             * Passing via 'header' ends up duplicating all custom headers submitted due to a bug in HHVM.
             * We can bypass this through the separate 'user_agent' field, as it is never sanitized,
             * so we can append our desired headers after the initial user-agent string.
             * Note that this works in php5 as well (probably 7 and up too),
             * but for now we use this only where we need it.
             * Source: https://github.com/facebook/hhvm/blob/master/hphp/runtime/base/http-stream-wrapper.cpp#L92
             */
            $this->options['http']['user_agent'] = "parse-php-sdk\r\n".$this->buildRequestHeaders();
        }

        // create a stream context
        $this->parseStream->createContext($this->options);

        // send our request
        $response = $this->parseStream->get($url);

        // get our response headers
        $rawHeaders = $this->parseStream->getResponseHeaders();

        // set any error and code
        $this->streamErrorMessage   = $this->parseStream->getErrorMessage();
        $this->streamErrorCode      = $this->parseStream->getErrorCode();

        if ($response !== false && $rawHeaders) {
            // set our response headers
            $this->responseHeaders = self::formatHeaders($rawHeaders);

            // get and set content type, if present
            if (isset($this->responseHeaders['Content-Type'])) {
                $this->responseContentType = $this->responseHeaders['Content-Type'];
            }

            // set our http status code
            $this->responseCode = self::getStatusCodeFromHeader($this->responseHeaders['http_code']);
        }

        // clear options
        $this->options = array();
        $this->httpOptions = array();

        // flush our existing headers
        $this->headers = array();

        return $response;
    }

    /**
     * Converts unformatted headers to an array of headers
     *
     * @param array $rawHeaders
     *
     * @return array
     */
    public static function formatHeaders(array $rawHeaders)
    {
        $headers = array();

        foreach ($rawHeaders as $line) {
            if (strpos($line, ':') === false) {
                // set our http status code
                $headers['http_code'] = $line;
            } else {
                // set this header entry
                list ($key, $value) = explode(': ', $line);
                $headers[$key]      = $value;
            }
        }

        return $headers;
    }
    /**
     * Extracts the Http status code from the given header
     *
     * @param string $header
     *
     * @return int
     */
    public static function getStatusCodeFromHeader($header)
    {
        preg_match('{HTTP/\d\.\d\s+(\d+)\s+.*}', $header, $match);
        return (int) $match[1];
    }

    /**
     * Gets the error code
     *
     * @return int
     */
    public function getErrorCode()
    {
        return $this->streamErrorCode;
    }

    /**
     * Gets the error message
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->streamErrorMessage;
    }

    /**
     * Sets a connection timeout. UNUSED in the stream client.
     *
     * @param int $timeout  Timeout to set
     */
    public function setConnectionTimeout($timeout)
    {
        // do nothing
    }

    /**
     * Sets the CA file to validate requests with
     *
     * @param string $caFile    CA file to set
     */
    public function setCAFile($caFile)
    {
        $this->caFile = $caFile;
    }

    /**
     * Sets http options to pass to the stream context
     * https://www.php.net/manual/en/context.php
     *
     * @param array $httpOptions    options to set
     */
    public function setHttpOptions($httpOptions)
    {
        $this->httpOptions = $httpOptions;
    }

    /**
     * Sets the request timeout
     *
     * @param int $timeout  Sets the timeout for the request
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }
}

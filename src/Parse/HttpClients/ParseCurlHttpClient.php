<?php
/**
 * Class ParseCurlHttpClient | Parse/HttpClients/ParseCurlHttpClient.php
 */

namespace Parse\HttpClients;

use Parse\ParseException;

/**
 * Class ParseCurlHttpClient - Curl http client
 *
 * @author Ben Friedman <friedman.benjamin@gmail.com>
 * @package Parse\HttpClients
 */
class ParseCurlHttpClient implements ParseHttpable
{
    /**
     * Curl handle
     *
     * @var ParseCurl
     */
    private $parseCurl;

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
     * cURL error code
     *
     * @var int
     */
    private $curlErrorCode;

    /**
     * cURL error message
     *
     * @var string
     */
    private $curlErrorMessage;

    /**
     * @const Curl Version which is unaffected by the proxy header length error.
     */
    const CURL_PROXY_QUIRK_VER = 0x071E00;

    /**
     * @const "Connection Established" header text
     */
    const CONNECTION_ESTABLISHED = "HTTP/1.0 200 Connection established\r\n\r\n";

    /**
     * Response from our request
     *
     * @var string
     */
    private $response;

    /**
     * ParseCurlHttpClient constructor.
     */
    public function __construct()
    {
        if (!isset($this->parseCurl)) {
            $this->parseCurl = new ParseCurl();
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
     * Builds and returns the coalesced request headers
     *
     * @return array
     */
    private function buildRequestHeaders()
    {
        // coalesce our header key/value pairs
        $headers = [];
        foreach ($this->headers as $key => $value) {
            $headers[] = $key.': '.$value;
        }

        return $headers;
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
     * Sets up our cURL request in advance
     */
    public function setup()
    {
        // init parse curl
        $this->parseCurl->init();

        $this->parseCurl->setOptionsArray(array(
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_HEADER          => 1,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_SSL_VERIFYHOST  => 2,
        ));
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

        if ($method == "GET" && !empty($data)) {
            // handle get
            $url .= '?'.http_build_query($data, null, '&');
        } elseif ($method == "POST") {
            // handle post
            $this->parseCurl->setOptionsArray(array(
                CURLOPT_POST        => 1,
                CURLOPT_POSTFIELDS  => $data
            ));
        } elseif ($method == "PUT") {
            // handle put
            $this->parseCurl->setOptionsArray(array(
                CURLOPT_CUSTOMREQUEST   => $method,
                CURLOPT_POSTFIELDS      => $data
            ));
        } elseif ($method == "DELETE") {
            // handle delete
            $this->parseCurl->setOption(CURLOPT_CUSTOMREQUEST, $method);
        }

        if (count($this->headers) > 0) {
            // set our custom request headers
            $this->parseCurl->setOption(CURLOPT_HTTPHEADER, $this->buildRequestHeaders());
        }

        // set url
        $this->parseCurl->setOption(CURLOPT_URL, $url);

        // perform our request and get our response
        $this->response = $this->parseCurl->exec();

        // get our response code
        $this->responseCode = $this->parseCurl->getInfo(CURLINFO_HTTP_CODE);

        // get our content type
        $this->responseContentType = $this->parseCurl->getInfo(CURLINFO_CONTENT_TYPE);

        // get any error code and message
        $this->curlErrorMessage = $this->parseCurl->getError();
        $this->curlErrorCode    = $this->parseCurl->getErrorCode();

        // calculate size of our headers
        $headerSize             = $this->getHeaderSize();

        // get and set response headers
        $headerContent          = trim(substr($this->response, 0, $headerSize));
        $this->responseHeaders  = $this->getHeadersArray($headerContent);

        // get our final response
        $response               = trim(substr($this->response, $headerSize));

        // close our handle
        $this->parseCurl->close();

        // flush our existing headers
        $this->headers = array();

        return $response;
    }

    /**
     * Convert and return response headers as an array
     * @param string $headerContent Raw headers to parse
     *
     * @return array
     */
    private function getHeadersArray($headerContent)
    {
        $headers = [];

        // normalize our line breaks
        $headerContent = str_replace("\r\n", "\n", $headerContent);

        // Separate our header sets, particularly if we followed a 301 redirect
        $headersSet = explode("\n\n", $headerContent);

        // Get the last set of headers, ignoring all others
        $rawHeaders = array_pop($headersSet);

        // sepearate our header components
        $headerComponents = explode("\n", $rawHeaders);

        foreach ($headerComponents as $component) {
            if (strpos($component, ': ') === false) {
                // set our http_code
                $headers['http_code'] = $component;
            } else {
                // set this header key/value pair
                list($key, $value) = explode(': ', $component);
                $headers[$key]      = $value;
            }
        }

        // return our completed headers
        return $headers;
    }


    /**
     * Sets the connection timeout
     *
     * @param int $timeout  Timeout to set
     */
    public function setConnectionTimeout($timeout)
    {
        $this->parseCurl->setOption(CURLOPT_CONNECTTIMEOUT, $timeout);
    }

    /**
     * Sets the request timeout
     *
     * @param int $timeout  Sets the timeout for the request
     */
    public function setTimeout($timeout)
    {
        $this->parseCurl->setOption(CURLOPT_TIMEOUT, $timeout);
    }

    /**
     * Sets the CA file to validate requests with
     *
     * @param string $caFile    CA file to set
     */
    public function setCAFile($caFile)
    {
        // name of a file holding one or more certificates to verify the peer with
        $this->parseCurl->setOption(CURLOPT_CAINFO, $caFile);
    }

    /**
     * Gets the error code
     *
     * @return int
     */
    public function getErrorCode()
    {
        return $this->curlErrorCode;
    }

    /**
     * Gets the error message
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->curlErrorMessage;
    }

    /**
     * Return proper header size
     *
     * @return integer
     */
    private function getHeaderSize()
    {
        $headerSize = $this->parseCurl->getInfo(CURLINFO_HEADER_SIZE);

        // This corrects a Curl bug where header size does not account
        // for additional Proxy headers.
        if ($this->needsCurlProxyFix()) {
            // Additional way to calculate the request body size.
            if (preg_match('/Content-Length: (\d+)/', $this->response, $match)) {
                $headerSize = mb_strlen($this->response) - $match[1];
            } elseif (stripos($this->response, self::CONNECTION_ESTABLISHED) !== false) {
                $headerSize += mb_strlen(self::CONNECTION_ESTABLISHED);
            }
        }
        return $headerSize;
    }

    /**
     * Detect versions of Curl which report incorrect header lengths when
     * using Proxies.
     *
     * @return boolean
     */
    private function needsCurlProxyFix()
    {
        $versionDat = curl_version();
        $version    = $versionDat['version_number'];

        return $version < self::CURL_PROXY_QUIRK_VER;
    }
}

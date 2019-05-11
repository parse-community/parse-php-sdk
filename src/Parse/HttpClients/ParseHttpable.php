<?php
/**
 * Class ParseHttpable | Parse/HttpClients/ParseHttpable.php
 */

namespace Parse\HttpClients;

/**
 * Class ParseHttpable - Interface for an HTTPable client
 *
 * @author Ben Friedman <friedman.benjamin@gmail.com>
 * @package Parse\HttpClients
 */
interface ParseHttpable
{
    /**
     * Adds a header to this request
     *
     * @param string $key       Header name
     * @param string $value     Header value
     */
    public function addRequestHeader($key, $value);

    /**
     * Gets headers in the response
     *
     * @return array
     */
    public function getResponseHeaders();

    /**
     * Returns the status code of the response
     *
     * @return int
     */
    public function getResponseStatusCode();

    /**
     * Returns the content type of the response
     *
     * @return null|string
     */
    public function getResponseContentType();

    /**
     * Sets the connection timeout
     *
     * @param int $timeout  Timeout to set
     */
    public function setConnectionTimeout($timeout);

    /**
     * Sets the request timeout
     *
     * @param int $timeout  Sets the timeout for the request
     */
    public function setTimeout($timeout);

    /**
     * Sets the CA file to validate requests with
     *
     * @param string $caFile    CA file to set
     */
    public function setCAFile($caFile);

    /**
     * Gets the error code
     *
     * @return int
     */
    public function getErrorCode();

    /**
     * Gets the error message
     *
     * @return string
     */
    public function getErrorMessage();

    /**
     * Sets up our client before we make a request
     */
    public function setup() : void;

    /**
     * Sends an HTTP request
     *
     * @param string $url       Url to send this request to
     * @param string $method    Method to send this request via
     * @param array $data       Data to send in this request
     * @return string
     */
    public function send($url, $method = 'GET', $data = array());
}

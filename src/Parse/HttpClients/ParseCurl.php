<?php
/**
 * Class ParseCurl | Parse/HttpClients/ParseCurl.php
 */

namespace Parse\HttpClients;

use Parse\ParseException;

/**
 * Class ParseCurl - Wrapper for abstracted curl usage
 *
 * @author Ben Friedman <ben@axolsoft.com>
 * @package Parse\HttpClients
 */
class ParseCurl
{
    /**
     * Curl handle
     *
     * @var resource
     */
    private $curl;

    /**
     * Sets up a new curl instance internally if needed
     */
    public function init()
    {
        if ($this->curl === null) {
            $this->curl = curl_init();
        }
    }

    /**
     * Executes this curl request
     *
     * @return mixed
     * @throws ParseException
     */
    public function exec()
    {
        if (!isset($this->curl)) {
            throw new ParseException('You must call ParseCurl::init first');
        }

        return curl_exec($this->curl);
    }

    /**
     * Sets a curl option
     *
     * @param int   $option Option to set
     * @param mixed $value  Value to set for this option
     * @throws ParseException
     */
    public function setOption($option, $value)
    {
        if (!isset($this->curl)) {
            throw new ParseException('You must call ParseCurl::init first');
        }

        curl_setopt($this->curl, $option, $value);
    }

    /**
     * Sets multiple curl options
     *
     * @param array $options    Array of options to set
     * @throws ParseException
     */
    public function setOptionsArray($options)
    {
        if (!isset($this->curl)) {
            throw new ParseException('You must call ParseCurl::init first');
        }

        curl_setopt_array($this->curl, $options);
    }

    /**
     * Gets info for this curl handle
     *
     * @param int $info Constatnt for info to get
     * @return mixed
     * @throws ParseException
     */
    public function getInfo($info)
    {
        if (!isset($this->curl)) {
            throw new ParseException('You must call ParseCurl::init first');
        }

        return curl_getinfo($this->curl, $info);
    }

    /**
     * Gets the curl error message
     *
     * @return string
     * @throws ParseException
     */
    public function getError()
    {
        if (!isset($this->curl)) {
            throw new ParseException('You must call ParseCurl::init first');
        }

        return curl_error($this->curl);
    }

    /**
     * Gets the curl error code
     *
     * @return int
     * @throws ParseException
     */
    public function getErrorCode()
    {
        if (!isset($this->curl)) {
            throw new ParseException('You must call ParseCurl::init first');
        }

        return curl_errno($this->curl);
    }

    /**
     * Closed our curl handle and disposes of it
     */
    public function close()
    {
        if (!isset($this->curl)) {
            throw new ParseException('You must call ParseCurl::init first');
        }

        // close our handle
        curl_close($this->curl);

        // unset our curl handle
        $this->curl = null;
    }
}
